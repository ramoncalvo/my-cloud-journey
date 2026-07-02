<?php

namespace App\Aws;

use App\Shared\Layout;
use Illuminate\Http\Request;

class AwsController
{
    private const LABEL = "AWS";

    public function publicView(Request $request)
    {
        $user = session("user_aws");
        $body = $user
            ? '<p>Sesion iniciada en ' . self::LABEL . ' como <strong>' . e($user["email"] ?? $user["username"] ?? "usuario") . '</strong>.</p>
               <a class="btn" href="/aws/private">Vista privada</a>
               <a class="btn" href="/auth/aws/logout">Cerrar sesion</a>'
            : '<p>Esta pagina es publica, no requiere autenticacion.</p>
               <a class="btn" href="/auth/aws/login">Iniciar sesion con ' . self::LABEL . '</a>';
        return response(Layout::render("<h1>" . self::LABEL . "</h1>$body"));
    }

    public function privateView(Request $request)
    {
        $user = session("user_aws");
        if (!$user) {
            return redirect("/auth/aws/login");
        }
        $body = "<pre>" . e(json_encode($user, JSON_PRETTY_PRINT)) . "</pre>
            <a class=\"btn\" href=\"/auth/aws/logout\">Cerrar sesion</a>";
        return response(Layout::render("<h1>" . self::LABEL . "</h1>$body"));
    }

    public function login(Request $request)
    {
        $provider = AwsClient::provider();
        $authUrl = $provider->getAuthorizationUrl(["scope" => "openid email profile"]);
        session(["state_aws" => $provider->getState()]);
        return redirect($authUrl);
    }

    public function callback(Request $request)
    {
        if ($request->query("state") !== session("state_aws")) {
            abort(400, "state invalido");
        }
        $provider = AwsClient::provider();
        try {
            $token = $provider->getAccessToken("authorization_code", ["code" => $request->query("code")]);
            // Igual que en las otras implementaciones: se pide el
            // resource owner (userinfo endpoint) en vez de decodificar
            // el id_token.
            session(["user_aws" => $provider->getResourceOwner($token)->toArray()]);
            return redirect("/aws/private");
        } catch (\Throwable $e) {
            return response(Layout::render("<pre>" . e($e->getMessage()) . "</pre>"), 500);
        }
    }

    public function logout(Request $request)
    {
        session()->forget("user_aws");
        return redirect("/aws");
    }
}
