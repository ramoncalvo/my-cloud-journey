<?php

function aws_logout(): void
{
    unset($_SESSION["user_aws"]);
    redirectTo("/aws");
}
