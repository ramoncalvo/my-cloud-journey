<?php

namespace App\Gcp;

use App\Shared\Layout;
use Illuminate\Http\Request;

class GcpController
{
    private const LABEL = "Google Cloud";

    public function publicView(Request $request)
    {
        $user = session("user_gcp");
        $body = $user
            ? '<p>Sesion iniciada en ' . self::LABEL . ' como <strong>' . e($user["email"] ?? $user["username"] ?? "usuario") . '</strong>.</p>
               <a class="btn" href="/gcp/private">Vista privada</a>
               <a class="btn" href="/auth/gcp/logout">Cerrar sesion</a>'
            : '<p>Esta pagina es publica, no requiere autenticacion.</p>
               <a class="btn" href="/auth/gcp/login">Iniciar sesion con ' . self::LABEL . '</a>';
        return response(Layout::render("<h1>" . self::LABEL . "</h1>$body"));
    }

    public function privateView(Request $request)
    {
        $user = session("user_gcp");
        if (!$user) {
            return redirect("/auth/gcp/login");
        }
        $body = "<pre>" . e(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>
            <a class=\"btn\" href=\"/auth/gcp/logout\">Cerrar sesion</a>";
        return response(Layout::render("<h1>" . self::LABEL . "</h1>$body"));
    }

    public function login(Request $request)
    {
        $provider = GcpClient::provider();
        $authUrl = $provider->getAuthorizationUrl(["scope" => "openid email profile"]);
        session(["state_gcp" => $provider->getState()]);
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if ($request->query("state") !== session("state_gcp")) {
            abort(400, "state invalido");
        }
        $provider = GcpClient::provider();
        try {
            $token = $provider->getAccessToken("authorization_code", ["code" => $request->query("code")]);
            session(["user_gcp" => $provider->getResourceOwner($token)->toArray()]);
            return redirect("/gcp/private");
        } catch (\Throwable $e) {
            return response(Layout::render("<pre>" . e($e->getMessage()) . "</pre>"), 500);
        }
    }

    public function logout(Request $request)
    {
        session()->forget("user_gcp");
        return redirect("/gcp");
    }
}
