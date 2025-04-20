<?php
// view_webpage.php – Displays the office webpage using the enhanced CMS structure.
// This file is responsible for fetching and including the templates for the sections in an office.
// The announcements template (if any) will be self-contained and fetch its own data.

require_once 'config.php';

// Ensure an office ID is provided.
if (!isset($_GET['office_id']) || empty($_GET['office_id'])) {
    echo "<div class='container mt-5'><div class='alert alert-info'>Please provide an office ID. For example: view_webpage.php?office_id=1</div></div>";
    exit();
}

$office_id = intval($_GET['office_id']);

// Retrieve office details.
$sqlOffice = "SELECT * FROM offices WHERE id = $office_id";
$resultOffice = $conn->query($sqlOffice);
if (!$resultOffice || $resultOffice->num_rows === 0) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Office not found.</div></div>";
    exit();
}
$office = $resultOffice->fetch_assoc();

// Log visitor data into office_visitors table.
$visitorIp = $_SERVER['REMOTE_ADDR'];
$stmtVisitor = $conn->prepare("INSERT INTO office_visitors (office_id, visitor_ip) VALUES (?, ?)");
$stmtVisitor->bind_param("is", $office_id, $visitorIp);
$stmtVisitor->execute();
$stmtVisitor->close();

// Get total visitors count for this office.
$sqlVisitorCount = "SELECT COUNT(*) AS total FROM office_visitors WHERE office_id = $office_id";
$resultVisitorCount = $conn->query($sqlVisitorCount);
$visitorCount = $resultVisitorCount ? $resultVisitorCount->fetch_assoc()['total'] : 0;

// Check admin mode.
$admin_mode = (isset($_GET['admin']) && $_GET['admin'] == 1);

// Fetch data sections.
$sqlSections = "SELECT * FROM sections WHERE office_id = $office_id ORDER BY created_at ASC";
$resultSections = $conn->query($sqlSections);
$navSections = [];
if ($resultSections && $resultSections->num_rows > 0) {
    while ($sec = $resultSections->fetch_assoc()) {
        $navSections[] = $sec;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($office['office_name']); ?></title>
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  <!-- Custom Styles -->
  <style>
    /* General resets and smooth scrolling */
    body, html {
      margin: 0;
      padding: 0;
      scroll-behavior: smooth;
    }
    /* Navigation Bar styling */
    nav.navbar {
      position: fixed;
      left: 0;
      right: 0;
      top: 0;
      z-index: 1040;
      width: 100%;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
      transition: top 0.3s ease-in-out;
    }
    /* Section styling */
    .section {
      padding: 20px 0;
      min-height: 100px;
    }
    @media (max-width: 767.98px) {
      .section {
        padding: 15px 0;
      }
      .static-logos {
        display: none;
      }
    }
  </style>
</head>
<body>
  <!-- Fixed Header -->
  <header>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-md navbar-light bg-light">
      <div class="container-fluid">
        <!-- Static Logos -->
        <div class="static-logos">
          <img src="ASSETS/Hi-Res-BAGONG-PILIPINAS-LOGO.png" alt="Static Logo 1" style="max-height: 60px;">
          <img src="ASSETS/LIBMANAN LOGO.png" alt="Static Logo 2" style="max-height: 60px;">
          <img src="ASSETS/big jNEW.png" alt="Static Logo 3" style="max-height: 60px;">
        </div>
        <a class="navbar-brand" href="#">
          <?php if (!empty($office['logo_path'])): ?>
            <img src="<?php echo htmlspecialchars($office['logo_path']); ?>" alt="<?php echo htmlspecialchars($office['office_name']); ?>" style="max-height: 65px; padding-left:10px;">
          <?php else: ?>
            <?php echo htmlspecialchars($office['office_name']); ?>
          <?php endif; ?>
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#officeNav">
          <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="officeNav">
          <ul class="navbar-nav ml-auto">
            <?php foreach ($navSections as $navSec): ?>
              <li class="nav-item">
                <a class="nav-link" href="#section-<?php echo htmlspecialchars($navSec['id']); ?>">
                  <?php echo ucfirst(str_replace('_', ' ', $navSec['section_type'])); ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>
    
  <!-- Dynamic Content Sections -->
  <?php if (!empty($navSections)): ?>
    <?php foreach ($navSections as $section): 
          $inlineStyle = "";
          if (!empty($section['background_color'])) {
            $inlineStyle .= "background-color: " . htmlspecialchars($section['background_color']) . "; ";
          }
          if (!empty($section['text_color'])) {
            $inlineStyle .= "color: " . htmlspecialchars($section['text_color']) . "; ";
          }
    ?>
      <section id="section-<?php echo htmlspecialchars($section['id']); ?>" class="section" style="<?php echo $inlineStyle; ?>">
        <?php 
          // Include the appropriate template for this section.
          // For example, if section_type is "announcements", the following file is included:
          $templateFile = "templates/" . htmlspecialchars($section['section_type']) . ".php";
          if (file_exists($templateFile)) {
            include $templateFile;
          } else {
            echo "<div class='alert alert-warning'>Template for '" . htmlspecialchars($section['section_type']) . "' not found.</div>";
          }
        ?>
      </section>
    <?php endforeach; ?>
  <?php else: ?>
    <p>No sections have been added for this office yet.</p>
  <?php endif; ?>

  <script>
// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
  // Recursive function to walk through all DOM nodes
  function walk(node) {
    // Process element, document, or fragment nodes by traversing their children
    if (node.nodeType === Node.ELEMENT_NODE || 
        node.nodeType === Node.DOCUMENT_NODE ||
        node.nodeType === Node.DOCUMENT_FRAGMENT_NODE) {
      node.childNodes.forEach(child => walk(child));
    }
    // Process text nodes
    else if (node.nodeType === Node.TEXT_NODE) {
      node.textContent = node.textContent.replace(/Hero/g, 'Home');
    }
  }
  
  // Start the DOM walk from the document's body
  walk(document.body);
});
</script>

  <!-- Scripts -->
  <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>