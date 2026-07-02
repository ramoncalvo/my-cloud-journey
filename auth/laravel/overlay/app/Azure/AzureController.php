<?php

namespace App\Azure;

use App\Shared\Layout;
use Illuminate\Http\Request;

class AzureController
{
    private const LABEL = "Azure";

    public function publicView(Request $request)
    {
        $user = session("user_azure");
        $body = $user
            ? '<p>Sesion iniciada en ' . self::LABEL . ' como <strong>' . e($user["email"] ?? $user["username"] ?? "usuario") . '</strong>.</p>
               <a class="btn" href="/azure/private">Vista privada</a>
               <a class="btn" href="/auth/azure/logout">Cerrar sesion</a>'
            : '<p>Esta pagina es publica, no requiere autenticacion.</p>
               <a class="btn" href="/auth/azure/login">Iniciar sesion con ' . self::LABEL . '</a>';
        return response(Layout::render("<h1>" . self::LABEL . "</h1>$body"));
    }

    public function privateView(Request $request)
    {
        $user = session("user_azure");
        if (!$user) {
            return redirect("/auth/azure/login");
        }
        $body = "<pre>" . e(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>
            <a class=\"btn\" href=\"/auth/azure/logout\">Cerrar sesion</a>";
        return response(Layout::render("<h1>" . self::LABEL . "</h1>$body"));
    }

    public function login(Request $request)
    {
        $provider = AzureClient::provider();
        $authUrl = $provider->getAuthorizationUrl(["scope" => "openid email profile"]);
        session(["state_azure" => $provider->getState()]);
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if ($request->query("state") !== session("state_azure")) {
            abort(400, "state invalido");
        }
        $provider = AzureClient::provider();
        try {
            $token = $provider->getAccessToken("authorization_code", ["code" => $request->query("code")]);
            session(["user_azure" => $provider->getResourceOwner($token)->toArray()]);
            return redirect("/azure/private");
        } catch (\Throwable $e) {
            return response(Layout::render("<pre>" . e($e->getMessage()) . "</pre>"), 500);
        }
    }

    public function logout(Request $request)
    {
        session()->forget("user_azure");
        return redirect("/azure");
    }
}
