<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Office Management Dashboard</title>
  <!-- Bootstrap CSS (via CDN) -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-light">
    <div class="container-fluid">
      <a class="navbar-brand" href="#">Office Management</a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" 
              data-bs-target="#navbarNav" aria-controls="navbarNav" 
              aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <!-- Additional nav items can be added here -->
          <li class="nav-item">
            <!-- This link opens the Profile Settings modal -->
            <a class="nav-link" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
              Profile Settings
            </a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Modal Structure -->
  <div class="modal fade" id="profileModal" tabindex="-1" 
       aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"> <!-- modal-lg for a larger modal -->
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="profileModalLabel">Account Profile Settings</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" 
                  aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Include the profile settings form -->
          <?php include 'profile_settings_form.php'; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- Bootstrap JS Bundle with Popper (via CDN) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>