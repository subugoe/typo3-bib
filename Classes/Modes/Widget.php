<?php

namespace Ipf\Bib\Modes;

class Widget
{
    /**
     * Show and pass value.
     */
    const WIDGET_SHOW = 0;

    /**
     * Edit and pass value.
     */
    const WIDGET_EDIT = 1;

    /**
     * Don't show but pass value.
     */
    const WIDGET_SILENT = 2;

    /**
     * Don't show and don't pass value.
     */
    const WIDGET_HIDDEN = 3;
}
