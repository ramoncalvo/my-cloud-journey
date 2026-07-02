<?php

function azure_logout(): void
{
    unset($_SESSION["user_azure"]);
    redirectTo("/azure");
}
