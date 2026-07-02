<?php

function aws_callback(): void
{
    $config = aws_config();

    if (($_GET["state"] ?? null) !== ($_SESSION["state_aws"] ?? null)) {
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

    // Igual que en las otras implementaciones: se pide el userinfo
    // endpoint con el access_token en vez de decodificar el id_token.
    $_SESSION["user_aws"] = httpGetBearer($config["userinfo_endpoint"], $token["access_token"]);
    redirectTo("/aws/private");
}
