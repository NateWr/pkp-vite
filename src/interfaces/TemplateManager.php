<?php

namespace NateWr\vite\interfaces;

interface TemplateManager
{
    function addStyleSheet($name, $style, $args = []);
    function addJavaScript($name, $script, $args = []);
    function addHeader($name, $header, $args = []);
}