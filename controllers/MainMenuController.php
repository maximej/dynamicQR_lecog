<?php
namespace App\Controllers;

class MainMenuController
{
    public function showMenu($request = null, $response = null, $args = [])
    {
        $menuHtml = '<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
            <div class="container-fluid">
                <img src="/public/COG_Logo_1000.jpg" alt="Logo" class="menu-title-logo">
                <a class="navbar-brand text-dark" href="/index.php">QR.leCOG.fr</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-end margin-right-50" id="navbarNav">
                    <ul class="navbar-nav w-100 justify-content-end gap-2">
                        <li class="nav-item"><button type="button" onclick="location.href=\'/create.php\'" class="btn btn-success btn-search-mt0 mb-2 mb-lg-0 me-lg-2 w-100 w-lg-auto">Create QR Code</button></li>
                        <li class="nav-item"><button type="button" onclick="location.href=\'/list.php\'" class="btn btn-primary btn-search-mt0 mb-2 mb-lg-0 me-lg-2 w-100 w-lg-auto">QR Code List</button></li>
                        <li class="nav-item"><button type="button" onclick="location.href=\'/userlist.php\'" class="btn btn-info btn-search-mt0 mb-2 mb-lg-0 me-lg-2 w-100 w-lg-auto">User Management</button></li>
                        <li class="nav-item"><button type="button" onclick="location.href=\'/logout.php\'" class="btn btn-outline-danger btn-search-mt0 w-100 w-lg-auto">Logout</button></li>
                    </ul>
                </div>
            </div>
        </nav>';
        $menuHtml .= '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">';
        $menuHtml .= '<link rel="stylesheet" href="/public/index.css">';
        $menuHtml .= '<link rel="apple-touch-icon" sizes="180x180" href="/public/fav/favicon/apple-touch-icon.png">';
        $menuHtml .= '<link rel="icon" type="image/png" sizes="32x32" href="/public/fav/favicon/favicon-32x32.png">';
        $menuHtml .= '<link rel="icon" type="image/png" sizes="16x16" href="/public/fav/favicon/favicon-16x16.png">';
        $menuHtml .= '<link rel="manifest" href="/public/fav/favicon/site.webmanifest">';
        $menuHtml .= '<link rel="mask-icon" href="/public/fav/favicon/safari-pinned-tab.svg" color="#5bbad5">';
        $menuHtml .= '<link rel="shortcut icon" href="/public/fav/favicon/favicon.ico">';
        $menuHtml .= '<meta name="msapplication-TileColor" content="#da532c">';
        $menuHtml .= '<meta name="msapplication-config" content="/public/fav/favicon/browserconfig.xml">';
        $menuHtml .= '<meta name="theme-color" content="#ffffff">';
        $menuHtml .= '<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>';
        $menuHtml .= '<script>document.addEventListener("DOMContentLoaded", function() {\n  var navCollapse = document.getElementById("navbarNav");\n  var navToggler = document.querySelector(".navbar-toggler");\n  if (navCollapse && navToggler) {\n    var bsCollapse = bootstrap.Collapse.getOrCreateInstance(navCollapse);\n    navCollapse.addEventListener("click", function(e) {\n      if ((e.target.tagName === "A" || e.target.tagName === "BUTTON") && window.getComputedStyle(navToggler).display !== "none") {\n        if (navCollapse.classList.contains("show")) {\n          bsCollapse.hide();\n        }\n      }\n    });\n  }\n});</script>';
        if ($response && method_exists($response, 'getBody')) {
            $response->getBody()->write($menuHtml);
            return $response;
        } else {
            echo $menuHtml;
            return null;
        }
    }
}
