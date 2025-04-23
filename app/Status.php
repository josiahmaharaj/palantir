<?php

namespace App;

enum Status: string
{
    case todo = "todo";
    case editing = "editing";
    case exported = "exported";
    case uploaded = "uploaded";
}
