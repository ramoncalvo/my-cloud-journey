<?php

function gcp_logout(): void
{
    unset($_SESSION["user_gcp"]);
    redirectTo("/gcp");
}
