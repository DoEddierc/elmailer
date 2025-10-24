<?php

namespace App\Http\Controllers;

use App\Jobs\SendOneEmailJob;
use App\Support\MailScheduler;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CircularController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'asunto'        => ['required','string','max:150'],
            'cuerpo'        => ['required','string','max:10000'],
            'destinatarios' => ['required','array','min:1'],
            'intervalSec'   => ['nullable','integer','min:2','max:10'],
        ]);

        // Normaliza destinatarios (array de strings o [{email:...}])
        $emails = [];
        foreach ($data['destinatarios'] as $d) {
            if (is_string($d) && filter_var($d, FILTER_VALIDATE_EMAIL)) {
                $emails[] = $d;
            } elseif (is_array($d) && isset($d['email']) && filter_var($d['email'], FILTER_VALIDATE_EMAIL)) {
                $emails[] = $d['email'];
            }
        }

        if (empty($emails)) {
            return response()->json(['ok' => false, 'error' => 'Sin destinatarios válidos'], 422);
        }

        // ID temporal de campaña (solo en memoria Redis)
        $campaignId = Str::ulid();
        cache()->put("campaign:{$campaignId}:total", count($emails), 3600);
        cache()->put("campaign:{$campaignId}:sent", 0, 3600);
        cache()->put("campaign:{$campaignId}:status", 'queued', 3600);

        $interval = $data['intervalSec'] ?? 3;
        $delays = MailScheduler::assignDelays(count($emails), $interval, 1);

        $now = now();
        foreach ($emails as $i => $to) {
            SendOneEmailJob::dispatch(
                to: $to,
                subject: $data['asunto'],
                body: $data['cuerpo'],
                campaignId: $campaignId
            )->delay($now->copy()->addSeconds($delays[$i]));
        }

        cache()->put("campaign:{$campaignId}:status", 'sending', 3600);

        return response()->json([
            'ok'         => true,
            'campaignId' => $campaignId,
            'queued'     => count($emails),
            'intervalSec'=> $interval
        ], 202);
    }

    public function status(string $id)
    {
        $total  = (int) cache()->get("campaign:{$id}:total", 0);
        $sent   = (int) cache()->get("campaign:{$id}:sent", 0);
        $status = cache()->get("campaign:{$id}:status", $sent >= $total && $total > 0 ? 'done' : 'sending');

        if ($total > 0 && $sent >= $total) {
            cache()->put("campaign:{$id}:status", 'done', 1800);
        }

        return response()->json([
            'campaignId' => $id,
            'status'     => $status,
            'sent'       => $sent,
            'total'      => $total,
            'progress'   => $total ? round($sent * 100 / $total, 2) : 0
        ]);
    }
}
