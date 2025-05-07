<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Test Sidebar</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <script src="assets/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<button class="btn btn-primary m-3" data-bs-toggle="offcanvas" data-bs-target="#sidebarOffcanvas">
  ☰ Ouvrir Sidebar
</button>

<div class="offcanvas offcanvas-start" tabindex="-1" id="sidebarOffcanvas">
  <div class="offcanvas-header">
    <h5 class="offcanvas-title">Menu</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    Contenu du menu 🧭
  </div>
</div>

</body>
</html>
