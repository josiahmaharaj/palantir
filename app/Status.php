<?php

namespace App;

enum Status: string
{
    case Todo = "Todo";
    case Editing = "Editing";
    case Exported = "Exported";
    case Uploaded = "Uploaded";
}
