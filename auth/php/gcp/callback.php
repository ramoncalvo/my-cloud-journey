<?php

function gcp_callback(): void
{
    $config = gcp_config();

    if (($_GET["state"] ?? null) !== ($_SESSION["state_gcp"] ?? null)) {
        http_response_code(400);
        echo layout("<pre>state invalido</pre>");
        exit;
    }

    $token = httpPostForm($config["token_endpoint"], [
        "grant_type" => "authorization_code",
        "code" => $_GET["code"] ?? "",
        "redirect_uri" => $config["redirect_uri"],
        "client_id" => $config["client_id"],
        "client_secret" => $config["client_secret"],
    ]);

    if (empty($token["access_token"])) {
        http_response_code(500);
        echo layout("<pre>" . htmlspecialchars(json_encode($token)) . "</pre>");
        exit;
    }

    $_SESSION["user_gcp"] = httpGetBearer($config["userinfo_endpoint"], $token["access_token"]);
    redirectTo("/gcp/private");
}
