<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Test Dropdown</title>
  <link rel="stylesheet" href="assets/css/bootstrap.min.css">
  <script src="assets/js/bootstrap.bundle.min.js"></script>
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">

    <a class="navbar-brand" href="#">Dictionnaire</a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto">
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">Menu</a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="#">Lien 1</a></li>
            <li><a class="dropdown-item" href="#">Lien 2</a></li>
          </ul>
        </li>
      </ul>
    </div>
  </div>
</nav>

</body>
</html>
