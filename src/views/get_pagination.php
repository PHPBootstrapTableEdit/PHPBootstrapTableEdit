<?php

$html = '';
foreach ($arr as $key => $val) {

    $css_active = '';
    if ($val == $page) {
        $css_active = 'active';
    }

    if ($val == '...') {
        $html .= "<li class='page-item disabled'><a class='page-link' href='#'>...</a></li>\n";
    } else {
        $html .= "<li class='page-item $css_active'><a class='page-link' href='?_page=$val&$qs'>$val</a></li>\n";
    }

}

$css_prev = "disabled";
$prev = $page;
if ($page != 1) {
    $css_prev = "";
    $prev = $page - 1;
}

$css_next = "disabled";
$next = $page;
if ($page < $total_page) {
    $css_next = "";
    $next = $page + 1;
}

$html_final = "
<nav aria-label='pagination'>
<ul class='pagination {$this->css['pagination']}'>
    <li class='page-item $css_prev'><a class='page-link' href='?_page=$prev&$qs'><span>&laquo;</span></a></li>
    $html
    <li class='page-item $css_next'><a class='page-link' href='?_page=$next&$qs'><span>&raquo;</span></a></li>
</ul>
</nav>
";
