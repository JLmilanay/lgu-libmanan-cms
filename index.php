<?php
// Database Connection
include 'config.php';

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
  $message = htmlspecialchars(trim($_POST['message']));

  if ($email && $message) {
      // Prepare and Execute SQL Query
      $stmt = $conn->prepare("INSERT INTO messages (email, message) VALUES (?, ?)");
      $stmt->bind_param("ss", $email, $message);

      if ($stmt->execute()) {
          $response = ["status" => "success", "message" => "Your message has been successfully submitted!"];
      } else {
          $response = ["status" => "error", "message" => "Failed to submit your message. Please try again later."];
      }

      $stmt->close();
  } else {
      $response = ["status" => "error", "message" => "Invalid input. Please ensure all fields are correctly filled."];
  }

  echo json_encode($response);
  exit;
}
/*--------------------------------------------------------------------
  Helper Functions for Embedded Online Videos and Thumbnails
---------------------------------------------------------------------*/
function getYoutubeEmbed($url) {
    $video_id = '';
    if (strpos($url, 'youtube.com') !== false) {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);
        if (isset($params['v'])) {
            $video_id = $params['v'];
        }
    } elseif (strpos($url, 'youtu.be') !== false) {
        $path = parse_url($url, PHP_URL_PATH);
        $video_id = ltrim($path, '/');
    }
    if ($video_id) {
        return '<div class="ratio ratio-16x9"><iframe src="https://www.youtube.com/embed/' . htmlspecialchars($video_id) . '" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe></div>';
    }
    return false;
}

function getFacebookEmbed($url) {
    return '<div class="ratio ratio-16x9"><iframe src="https://www.facebook.com/plugins/video.php?href=' . urlencode($url) . '&show_text=0&width=560" width="560" height="315" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowfullscreen></iframe></div>';
}

function getTikTokEmbed($url) {
    $video_id = '';
    if (preg_match('/\/video\/(\d+)/', $url, $matches)) {
        $video_id = $matches[1];
    }
    if ($video_id) {
        return '<div class="ratio-tiktok"><iframe src="https://www.tiktok.com/embed/' . htmlspecialchars($video_id) . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe></div>';
    }
    return '<div class="ratio-tiktok"><iframe src="' . htmlspecialchars($url) . '" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe></div>';
}

function getDefaultEmbed($url) {
    return '<div class="ratio ratio-16x9"><iframe src="' . htmlspecialchars($url) . '" frameborder="0" allowfullscreen></iframe></div>';
}

function getEmbedCode($url) {
    if (strpos($url, 'youtube.com') !== false || strpos($url, 'youtu.be') !== false) {
        $embed = getYoutubeEmbed($url);
        if ($embed) return $embed;
    }
    if (strpos($url, 'facebook.com') !== false) {
        return getFacebookEmbed($url);
    }
    if (strpos($url, 'tiktok.com') !== false || strpos($url, 'vm.tiktok.com') !== false) {
        return getTikTokEmbed($url);
    }
    return getDefaultEmbed($url);
}

function getYoutubeThumbnail($url) {
    $video_id = '';
    if (strpos($url, 'youtube.com') !== false) {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);
        if (isset($params['v'])) {
            $video_id = $params['v'];
        }
    } elseif (strpos($url, 'youtu.be') !== false) {
        $path = parse_url($url, PHP_URL_PATH);
        $video_id = ltrim($path, '/');
    }
    if ($video_id) {
        return 'https://img.youtube.com/vi/' . htmlspecialchars($video_id) . '/hqdefault.jpg';
    }
    return false;
}

function getFacebookThumbnail($url) {
    $oembedUrl = "https://www.facebook.com/plugins/video/oembed.json/?url=" . urlencode($url);
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $json = @file_get_contents($oembedUrl, false, $context);
    if ($json) {
        $data = json_decode($json, true);
        if (!empty($data['thumbnail_url'])) {
            return $data['thumbnail_url'];
        }
        if (!empty($data['html'])) {
            preg_match('/src="([^"]+)"/i', $data['html'], $iframe);
            if (!empty($iframe[1])) {
                return $iframe[1];
            }
        }
    }
    $html = @file_get_contents($url, false, $context);
    if ($html) {
        preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $matches);
        if (!empty($matches[1])) {
            return $matches[1];
        }
    }
    return false;
}

function getTikTokThumbnail($url) {
    $oembedUrl = "https://www.tiktok.com/oembed?url=" . urlencode($url);
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36\r\n"
        ]
    ];
    $context = stream_context_create($options);
    $json = @file_get_contents($oembedUrl, false, $context);
    if ($json) {
        $data = json_decode($json, true);
        if (!empty($data['thumbnail_url'])) {
            return $data['thumbnail_url'];
        }
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/98.0.4758.102 Safari/537.36");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $html = curl_exec($ch);
    curl_close($ch);

    if ($html && preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $matches)) {
        return $matches[1];
    }
    return 'https://via.placeholder.com/360x640?text=No+Thumbnail';
}

function getThumbnail($url) {
    $platformHandlers = [
        'youtube.com'   => 'getYoutubeThumbnail',
        'youtu.be'      => 'getYoutubeThumbnail',
        'vimeo.com'     => 'getVimeoThumbnail',
        'facebook.com'  => 'getFacebookThumbnail',
        'fb.watch'      => 'getFacebookThumbnail',
        'tiktok.com'    => 'getTikTokThumbnail',
        'vm.tiktok.com' => 'getTikTokThumbnail',
    ];

    foreach ($platformHandlers as $keyword => $handler) {
        if (strpos($url, $keyword) !== false && function_exists($handler)) {
            $thumb = $handler($url);
            if ($thumb) return $thumb;
        }
    }
    return 'https://via.placeholder.com/640x360?text=No+Thumbnail';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Municipality of Libmanan</title>

  <!-- Bootstrap & Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Google Fonts -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&display=swap">

  <style>
    :root {
      --primary-color: #003366;
      --secondary-color: #a00000;
      --accent-color: #ffcc00;
      --light-bg: #f8f9fa;
      --dark-text: #212529;
    }

    body {
      font-family: 'Roboto', sans-serif;
      line-height: 1.6;
      color: var(--dark-text);
      overflow-x: hidden;
    }

    /* Adjusted Awards Carousel Positioning */
    #awardsCarousel {
      padding-top: 12%; /* Increased by 2% from the original 10% */
    }

    /* Header Styles */
    header {
      position: relative;
      height: 100vh;
      background-size: cover;
      background-position: center;
      background-attachment: fixed;
      color: white;
      display: flex;
      align-items: center;
      overflow: hidden;
    }

    header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1;
    }

    .header-content {
      position: relative;
      z-index: 2;
    }

    .header-title {
      font-family: 'Poppins', serif;
      font-size: 3.5rem;
      font-weight: 700;
      margin-bottom: 1.5rem;
      text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
    }

    .header-subtitle {
      font-size: 1.2rem;
      margin-bottom: 2rem;
      max-width: 600px;
      text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
    }

    /* Navbar Styles */
    .navbar {
      background: linear-gradient(135deg, var(--primary-color), #001f4d);
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      padding: 10px 0;
      transition: all 0.3s ease;
    }

    .navbar.scrolled {
      padding: 5px 0;
      background: rgba(0, 31, 77, 0.95);
      backdrop-filter: blur(10px);
    }

    .navbar-brand .logos {
      display: flex;
      align-items: center;
    }

    .navbar-brand img {
      height: 50px;
      margin-right: 10px;
    }

    .navbar-brand .text {
      display: flex;
      flex-direction: column;
    }

    .navbar-brand .large-text {
      font-size: 1.2rem;
      font-weight: bold;
      margin-bottom: -5px;
      color: white;
    }

    .navbar-brand .sub-text {
      font-size: 0.8rem;
      color: rgba(255, 255, 255, 0.8);
    }

    .nav-link {
      color: white !important;
      font-weight: 500;
      padding: 8px 15px !important;
      margin: 0 5px;
      border-radius: 5px;
      transition: all 0.3s ease;
    }

    .nav-link:hover,
    .nav-link.active {
      background: rgba(255, 255, 255, 0.2);
      transform: translateY(-2px);
    }

    /* Section Styles */
    .section {
      padding: 80px 0;
      position: relative;
      text-align: center;
    }

    .section-title {
      font-family: 'Poppins', serif;
      font-size: 2.5rem;
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 1.5rem;
      position: relative;
      display: inline-block;
    }
    .section-subtitle {
      font-size: 1.1rem;
      color: #6c757d;
      max-width: 700px;
      margin: 0 auto 3rem;
    }

    /* Card Styles */
    .card {
      border: none;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
      height: 100%;
    }

    .card:hover {
      transform: translateY(-10px);
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
    }

    .card-img-top {
      height: 200px;
      object-fit: cover;
    }

    .card-body {
      padding: 1.5rem;
    }

    .card-title {
      font-weight: 700;
      color: var(--primary-color);
      margin-bottom: 0.75rem;
    }

    /* Button Styles */
    .btn-primary {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      padding: 10px 25px;
      font-weight: 500;
      border-radius: 50px;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      background-color: #002244;
      border-color: #002244;
      transform: translateY(-2px);
    }

    .btn-outline-primary {
      color: var(--primary-color);
      border-color: var(--primary-color);
      padding: 10px 25px;
      font-weight: 500;
      border-radius: 50px;
      transition: all 0.3s ease;
    }

    .btn-outline-primary:hover {
      background-color: var(--primary-color);
      border-color: var(--primary-color);
      transform: translateY(-2px);
    }

    /* Emergency Contacts Section */
    #emergency {
      background: linear-gradient(135deg, var(--light-bg) 0%, #e9ecef 100%);
      padding: 80px 0;
    }

    .emergency-title {
      color: var(--secondary-color);
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }

    .emergency-subtitle {
      font-size: 1.1rem;
      max-width: 800px;
      margin: 0 auto 3rem;
    }

    .hotline-card {
      background: white;
      border-radius: 15px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      overflow: hidden;
      transition: all 0.3s ease;
      display: flex;
      flex-direction: column;
      height: 100%;
    }

    .hotline-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    }

    .card-header {
      padding: 20px;
      text-align: center;
      background: linear-gradient(135deg, var(--primary-color), #001f4d);
      color: white;
    }

    .card-header h3 {
      margin: 15px 0 0;
      font-size: 1.3rem;
    }

    .hotline-logo {
      width: 60px;
      height: 60px;
      object-fit: contain;
      background: white;
      padding: 10px;
      border-radius: 50%;
    }

    .contact-info {
      margin-bottom: 15px;
    }

    .contact-item {
      display: flex;
      align-items: center;
      margin-bottom: 10px;
      font-size: 0.95rem;
    }

    .contact-item i {
      margin-right: 10px;
      color: var(--primary-color);
      width: 20px;
      text-align: center;
    }

    .additional-notes {
      background: var(--light-bg);
      padding: 10px;
      border-radius: 8px;
      font-size: 0.9rem;
      color: #555;
    }

    .card-footer {
      padding: 10px 20px;
      background: #f1f1f1;
      text-align: center;
    }

    .badge {
      display: inline-block;
      padding: 5px 15px;
      background: var(--secondary-color);
      color: white;
      border-radius: 20px;
      font-size: 0.8rem;
      text-transform: uppercase;
    }

    /* Responsive Styles */
    @media (max-width: 992px) {
      .header-title {
        font-size: 2.5rem;
      }
      .section {
        padding: 60px 0;
      }
    }

    @media (max-width: 768px) {
      .header-title {
        font-size: 2rem;
      }
      .header-subtitle {
        font-size: 1rem;
      }
      .section-title {
        font-size: 2rem;
      }
      .navbar-brand .large-text {
        font-size: 1rem;
      }
      .navbar-brand .sub-text {
        font-size: 0.7rem;
      }
    }

    @media (max-width: 576px) {
      header {
        height: auto;
        min-height: 100vh;
        padding: 100px 0 60px;
      }
      .header-title {
        font-size: 1.8rem;
      }
      .section {
        padding: 40px 0;
      }
    }

    /* TikTok Portrait Ratio */
    .ratio-tiktok {
      position: relative;
      width: 100%;
      padding-bottom: 177.78%;
    }
    .ratio-tiktok iframe {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
    }

    /* News Carousel Styles - Gradient Background for Captions */
    #newsCarousel .carousel-item img {
      height: 500px;
      object-fit: cover;
    }
    #newsCarousel .carousel-caption {
      background: linear-gradient(45deg, rgba(0, 0, 0, 0.8), rgba(0, 0, 0, 0.4));
      padding: 1rem;
      border-radius: 5px;
    }

    /* Gallery Continuous Scrolling Styles */
    .gallery-wrapper {
      overflow: hidden;
      width: 100%;
    }
    .gallery-carousel {
      display: flex;
      animation: galleryScroll 20s linear infinite;
    }
    .gallery-card {
      flex: 0 0 auto;
      width: 300px;
      height: 350px;
      margin-right: 30px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      background: #fff;
    }
    .gallery-card .gallery-media {
      height: 70%;
      width: 100%;
      object-fit: cover;
    }
    .gallery-card .card-body {
      height: 30%;
      padding: .5rem;
      display: flex;
      align-items: center;
      justify-content: center;
      background: #fff;
    }
    @keyframes galleryScroll {
      0% {
        transform: translateX(0);
      }
      100% {
        transform: translateX(-50%);
      }
    }

    section#contact-us {
    padding: 80px 0;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    text-align: center;
}

form {
    background: white;
    padding: 30px;
    border-radius: 10px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
}

form .form-label {
    font-weight: 600;
    color: #212529;
}

form .btn {
    background: var(--primary-color);
    border: none;
    color: white;
    transition: transform 0.3s ease;
}

form .btn:hover {
    transform: scale(1.05);
}

.alert {
    margin-top: 15px;
    transition: all 0.3s ease;
}


  </style>
</head>

<body>
  <!-- Navigation Bar -->
  <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
    <div class="container">
      <a class="navbar-brand" href="#">
        <div class="logos">
          <img src="ASSETS/Hi-Res-BAGONG-PILIPINAS-LOGO.png" alt="Logo 1" class="desktop-logo">
          <img src="ASSETS/LIBMANAN LOGO.png" alt="Logo 2" class="mobile-logo">
          <img src="ASSETS/big jNEW.png" alt="Logo 3" class="desktop-logo">
        </div>
        <div class="text ms-2">
          <div class="large-text">LOCAL GOVERNMENT UNIT OF LIBMANAN</div>
          <div class="sub-text">PROVINCE OF CAMARINES SUR</div>
        </div>
      </a>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav ms-auto">
          <li class="nav-item"><a class="nav-link active" href="#">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="#offices">Offices</a></li>
          <li class="nav-item"><a class="nav-link" href="#services">Services</a></li>
          <li class="nav-item"><a class="nav-link" href="#announcements">Announcements</a></li>
          <li class="nav-item"><a class="nav-link" href="#news">News</a></li>
          <li class="nav-item"><a class="nav-link" href="#gallery">Gallery</a></li>
          <li class="nav-item"><a class="nav-link" href="#documents">Documents</a></li>
          <li class="nav-item"><a class="nav-link" href="#emergency">Emergency</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <!-- Header Section (Home) -->
  <header class="text-center">
    <div class="container header-content">
      <div class="row align-items-center">
        <div class="col-lg-6 text-lg-start">
          <h1 class="header-title">Welcome to the Municipality of Libmanan</h1>
          <p class="header-subtitle">A progressive community dedicated to sustainable development and excellent public service</p>
          <a href="#offices" class="btn btn-primary btn-lg rounded-pill shadow">Explore Our Services</a>
        </div>
        <div class="col-lg-6">
          <div id="awardsCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner rounded">
              <?php
              $result = $conn->query("SELECT award_image_url, award_title FROM lgu_main_awards WHERE award_status = 'active'");
              $first = true;
              while ($row = $result->fetch_assoc()) {
                  $image_path = ltrim($row['award_image_url'], './');
                  echo "<div class='carousel-item " . ($first ? "active" : "") . "'>
                      <img src='$image_path' class='d-block w-100' alt='{$row['award_title']}'>
                      <div class='carousel-caption d-none d-md-block'>
                        <h5>{$row['award_title']}</h5>
                      </div>
                    </div>";
                  $first = false;
              }
              ?>
            </div>
            <button class="carousel-control-prev" type="button" data-bs-target="#awardsCarousel" data-bs-slide="prev">
              <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#awardsCarousel" data-bs-slide="next">
              <span class="carousel-control-next-icon" aria-hidden="true"></span>
            </button>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- Offices Section -->
  <section id="offices" class="section">
    <div class="container">
      <h2 class="section-title text-center">Municipal Offices</h2>
      <p class="section-subtitle text-center">The municipal offices of Libmanan serve as the hub for local governance, providing essential public services and community development programs.</p>

      <div class="row g-4">
        <?php
        $result = $conn->query("SELECT office_id, office_name, office_description, office_image_url, office_link FROM lgu_main_offices WHERE office_status = 'active' LIMIT 6");
        while ($row = $result->fetch_assoc()) {
            $image_path = ltrim($row['office_image_url'], './');
            echo '
          <div class="col-md-6 col-lg-4">
            <div class="card h-100">
              <img src="' . $image_path . '" class="card-img-top" alt="' . htmlspecialchars($row['office_name']) . '">
              <div class="card-body">
                <h5 class="card-title">' . htmlspecialchars($row['office_name']) . '</h5>
                <p class="card-text">' . htmlspecialchars(substr($row['office_description'], 0, 100)) . '...</p>
                <a href="' . $row['office_link'] . '" class="btn btn-outline-primary">Learn More</a>
              </div>
            </div>
          </div>';
        }
        ?>
      </div>

      <div class="text-center mt-4">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#allOfficesModal">View All Offices</button>
      </div>
    </div>
  </section>

  <!-- Modal for All Offices -->
  <div class="modal fade" id="allOfficesModal" tabindex="-1" aria-labelledby="allOfficesModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="allOfficesModalLabel">All Municipal Offices</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="row g-4">
            <?php
            $all_offices = $conn->query("SELECT office_name, office_description, office_image_url, office_link FROM lgu_main_offices WHERE office_status = 'active'");
            while ($row = $all_offices->fetch_assoc()) {
                $image_path = ltrim($row['office_image_url'], './');
                echo '
              <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                  <img src="' . $image_path . '" class="card-img-top" alt="' . htmlspecialchars($row['office_name']) . '">
                  <div class="card-body">
                    <h5 class="card-title">' . htmlspecialchars($row['office_name']) . '</h5>
                    <p class="card-text">' . htmlspecialchars(substr($row['office_description'], 0, 100)) . '...</p>
                    <a href="' . $row['office_link'] . '" class="btn btn-outline-primary">Learn More</a>
                  </div>
                </div>
              </div>';
            }
            ?>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Services Section -->
  <section id="services" class="section bg-light">
    <div class="container">
      <h2 class="section-title text-center">Our Services</h2>
      <p class="section-subtitle text-center">The LGU-Libmanan offers a wide range of services designed to enhance the quality of life for residents.</p>

      <div class="services-wrapper">
        <div class="services-carousel">
          <?php
          $result = $conn->query("SELECT s.service_name, s.service_description, s.service_icon_url, o.office_name 
                                       FROM lgu_main_services s 
                                       JOIN lgu_main_offices o ON s.office_id = o.office_id 
                                       WHERE s.service_status = 'active' 
                                       LIMIT 6");
          while ($row = $result->fetch_assoc()) {
              $icon_path = ltrim($row['service_icon_url'], './');
              echo '
              <div class="service-item">
                <div class="card h-100">
                  <div class="card-body text-center">
                    <img src="' . $icon_path . '" alt="' . htmlspecialchars($row['service_name']) . '" class="img-fluid mb-3" style="height: 60px;">
                    <h5 class="card-title">' . htmlspecialchars($row['service_name']) . '</h5>
                    <p class="card-text">' . htmlspecialchars(substr($row['service_description'], 0, 120)) . '...</p>
                    <small class="text-muted">Offered by: ' . htmlspecialchars($row['office_name']) . '</small>
                  </div>
                </div>
              </div>';
          }
          ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Custom CSS and JS for Infinite Scrolling for Services and Gallery -->
  <style>
    .services-wrapper {
      overflow: hidden;
      width: 100%;
    }
    .services-carousel {
      display: flex;
      animation: scrollInfinite 20s linear infinite;
    }
    .service-item {
      flex: 0 0 auto;
      width: 300px;
      margin-right: 30px;
    }
    @keyframes scrollInfinite {
      0% {
        transform: translateX(0);
      }
      100% {
        transform: translateX(-100%);
      }
    }
    .services-carousel::after {
      content: "";
      flex: 0 0 0;
    }
  </style>
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const servicesCarousel = document.querySelector('.services-carousel');
      const servicesItems = servicesCarousel.innerHTML;
      servicesCarousel.innerHTML += servicesItems;

      const galleryCarousel = document.querySelector('.gallery-carousel');
      if (galleryCarousel) {
        const galleryItems = galleryCarousel.innerHTML;
        galleryCarousel.innerHTML += galleryItems;
      }
    });
  </script>

  <!-- Announcements Section -->
  <section id="announcements" class="section">
    <div class="container">
      <h2 class="section-title text-center">Announcements</h2>
      <p class="section-subtitle text-center">Stay informed with the latest updates and public advisories from the Local Government Unit.</p>

      <div id="announcementCarousel" class="carousel slide" data-bs-ride="carousel">
         <div class="carousel-inner">
            <?php
            $result = $conn->query("SELECT announcement_id, announcement_title, announcement_content, announcement_image_url, announcement_date 
                                   FROM lgu_main_announcements 
                                   WHERE announcement_status = 'published' 
                                   ORDER BY announcement_date DESC 
                                   LIMIT 3");
            $first = true;
            while ($row = $result->fetch_assoc()){
               $image_path = ltrim($row['announcement_image_url'], './');
               $date = date("F j, Y", strtotime($row['announcement_date']));
               echo "<div class='carousel-item " . ($first ? "active" : "") . "'>";
               echo "<div class='card announcement-card' data-bs-toggle='modal' data-bs-target='#announcementModal' 
                    data-title='".htmlspecialchars($row['announcement_title'], ENT_QUOTES)."'
                    data-content='".htmlspecialchars($row['announcement_content'], ENT_QUOTES)."'
                    data-image='".htmlspecialchars($image_path, ENT_QUOTES)."'
                    data-date='".htmlspecialchars($date, ENT_QUOTES)."'>
                        <img src='$image_path' class='card-img-top' alt='".htmlspecialchars($row['announcement_title'])."'>
                        <div class='card-body'>
                           <h5 class='card-title'>".htmlspecialchars($row['announcement_title'])."</h5>
                           <p class='card-text'>".htmlspecialchars(substr($row['announcement_content'], 0, 100))."...</p>
                           <small class='text-muted'>Posted: $date</small>
                        </div>
                    </div>";
               echo "</div>";
               $first = false;
            }
            ?>
         </div>
         <button class="carousel-control-prev" type="button" data-bs-target="#announcementCarousel" data-bs-slide="prev">
           <span class="carousel-control-prev-icon" aria-hidden="true"></span>
         </button>
         <button class="carousel-control-next" type="button" data-bs-target="#announcementCarousel" data-bs-slide="next">
           <span class="carousel-control-next-icon" aria-hidden="true"></span>
         </button>
      </div>

    </div>
  </section>

  <!-- Modal for Full Announcement View -->
  <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="announcementModalLabel">Announcement Title</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <img src="" id="announcementModalImage" class="img-fluid rounded w-100 mb-4" alt="Announcement Image">
          <p><small class="text-muted" id="announcementModalDate"></small></p>
          <p id="announcementModalContent" style="font-size: 1.2rem;"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- News Section -->
  <section id="news" class="section bg-light py-5">
    <div class="container">
      <h2 class="section-title text-center">Latest News</h2>
      <p class="section-subtitle text-center">Stay updated on community projects, government initiatives, and local events.</p>

      <div id="newsCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner rounded">
          <?php
          $result = $conn->query("SELECT news_title, news_content, news_image_url, news_date 
                               FROM lgu_main_news 
                               WHERE news_status = 'published' 
                               ORDER BY news_date DESC 
                               LIMIT 5");
          $first = true;
          while ($row = $result->fetch_assoc()) {
              $image_path = ltrim($row['news_image_url'], './');
              $date = date("F j, Y", strtotime($row['news_date']));
              echo '
            <div class="carousel-item ' . ($first ? 'active' : '') . '">
              <img src="' . $image_path . '" 
                   class="d-block w-100 img-fluid rounded news-modal-trigger" 
                   alt="' . htmlspecialchars($row['news_title']) . '" 
                   data-bs-toggle="modal" 
                   data-bs-target="#newsModal"
                   data-title="' . htmlspecialchars($row['news_title']) . '"
                   data-content="' . htmlspecialchars($row['news_content']) . '"
                   data-image="' . $image_path . '"
                   data-date="' . $date . '">
              <div class="carousel-caption">
                <div class="carousel-caption-inner">
                  <h5>' . htmlspecialchars($row['news_title']) . '</h5>
                  <p>' . htmlspecialchars(substr($row['news_content'], 0, 100)) . '...</p>
                  <small>Posted: ' . $date . '</small>
                </div>
              </div>
            </div>';
              $first = false;
          }
          ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev">
          <span class="carousel-control-prev-icon" aria-hidden="true"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next">
          <span class="carousel-control-next-icon" aria-hidden="true"></span>
        </button>
      </div>
    </div>
  </section>

  <!-- Modal for News -->
  <div class="modal fade" id="newsModal" tabindex="-1" aria-labelledby="newsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <div class="w-100">
            <h5 class="modal-title fw-bold mb-1" id="newsModalLabel">News Title</h5>
            <small class="text-muted d-block" id="modalDate" style="font-size: 1rem;"></small>
          </div>
          <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <img src="" class="img-fluid rounded w-100 mb-4" id="modalImage" alt="News Image" style="max-height: 700px; object-fit: contain;">
          <p id="modalContent" style="font-size: 1.2rem;"></p>
        </div>
      </div>
    </div>
  </div>

  <!-- Gallery Section -->
  <section id="gallery" class="section">
    <div class="container">
      <h2 class="section-title text-center">Gallery</h2>
      <p class="section-subtitle text-center">Explore moments from various events, programs, and initiatives.</p>
      <div class="gallery-wrapper">
        <div class="gallery-carousel">
          <?php
          $result = $conn->query("SELECT gallery_image_url, gallery_caption, gallery_type FROM lgu_main_gallery WHERE gallery_status = 'active' ORDER BY gallery_date DESC");
          while ($row = $result->fetch_assoc()) {
              $media_tag = '';
              if ($row['gallery_type'] == 'image') {
                  $media_path = ltrim($row['gallery_image_url'], './');
                  $media_tag = '<img src="' . $media_path . '" class="img-fluid gallery-clickable gallery-media" data-type="image" data-src="' . $media_path . '" alt="' . htmlspecialchars($row['gallery_caption']) . '">';
              } elseif ($row['gallery_type'] == 'video') {
                  $media_path = ltrim($row['gallery_image_url'], './');
                  $media_tag = '<video class="img-fluid gallery-clickable gallery-media" muted data-type="video" data-src="' . $media_path . '">
                                    <source src="' . $media_path . '" type="video/mp4">
                                    Your browser does not support the video tag.
                                </video>';
              } elseif ($row['gallery_type'] == 'link') {
                  $video_link = trim($row['gallery_image_url']);
                  $thumbnail = getThumbnail($video_link);
                  $embed_code = getEmbedCode($video_link);
                  $data_embed = htmlspecialchars($embed_code, ENT_QUOTES);
                  $media_tag = '<img src="' . $thumbnail . '" class="img-fluid gallery-clickable gallery-media" data-type="link" data-video="' . $video_link . '" data-embed=\'' . $data_embed . '\' alt="' . htmlspecialchars($row['gallery_caption']) . '">';
              }
              echo '<div class="gallery-card">';
                    echo '<div class="card border-0 h-100">';
                          echo $media_tag;
                          echo '<div class="card-body text-center p-2">';
                              echo '<p class="card-text small mb-0">' . htmlspecialchars($row['gallery_caption']) . '</p>';
                          echo '</div>';
                    echo '</div>';
              echo '</div>';
          }
          ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Modal for Single Gallery Item Preview -->
  <div class="modal fade" id="galleryModal" tabindex="-1" aria-labelledby="galleryModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
      <div class="modal-content bg-transparent border-0">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" style="position: absolute; top: 10px; right: 20px; z-index: 1000;"></button>
        <div class="modal-body p-0 text-center" style="overflow: hidden; max-width: 100%;">
          <span id="modalMediaContainer" role="region" aria-live="polite"></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Documents Section -->
  <section id="documents" class="section bg-light">
    <div class="container">
      <h2 class="section-title text-center">Public Documents</h2>
      <p class="section-subtitle text-center">Access important forms, guidelines, and official documents.</p>

      <div class="row g-4">
        <?php
        $result = $conn->query("SELECT document_title, document_url, document_type 
                                       FROM lgu_main_documents 
                                       WHERE document_status = 'active' 
                                       ORDER BY created_at DESC 
                                       LIMIT 6");
        while ($row = $result->fetch_assoc()) {
            // Use the document URL directly
            $file_path = ltrim($row['document_url'], './');
            $file_icon = $row['document_type'] == 'pdf' ? 'fa-file-pdf' : 'fa-file-word';
            echo '
          <div class="col-md-6 col-lg-4">
            <div class="card h-100">
              <div class="card-body">
                <div class="d-flex align-items-center">
                  <i class="fas ' . $file_icon . ' fa-3x text-danger me-3"></i>
                  <div>
                    <h5 class="card-title mb-1">' . $row['document_title'] . '</h5>
                    <small class="text-muted">' . strtoupper($row['document_type']) . ' Document</small>
                  </div>
                </div>
              </div>
              <div class="card-footer bg-transparent">
                <a href="' . $file_path . '" class="btn btn-sm btn-outline-primary w-100" download>
                  <i class="fas fa-download me-2"></i>Download
                </a>
              </div>
            </div>
          </div>';
        }
        ?>
      </div>

      <div class="text-center mt-4">
        <a href="#" class="btn btn-primary">View All Documents</a>
      </div>
    </div>
  </section>

  <!-- Emergency Contacts Section -->
  <section id="emergency" class="section py-5">
    <div class="container">
      <h2 class="text-center text-danger fw-bold mb-3">Emergency Hotlines</h2>
      <p class="text-center text-muted mb-5">
        In case of emergencies, please contact the following offices immediately.
      </p>

      <div class="row g-4">
        <?php
        $result = $conn->query("SELECT emergency_name, emergency_phone, emergency_alt_phone, emergency_address, emergency_logo, emergency_category, emergency_notes 
                                    FROM lgu_main_emergency 
                                    WHERE emergency_status = 'active' 
                                    ORDER BY emergency_category");

        while ($row = $result->fetch_assoc()) {
            $logo_path = ltrim($row['emergency_logo'], './');
            $category = strtolower($row['emergency_category']);
            switch ($category) {
                case 'police':
                    $icon = 'fa-user-shield';
                    break;
                case 'hospital':
                    $icon = 'fa-truck-medical';
                    break;
                case 'fire':
                    $icon = 'fa-fire-extinguisher';
                    break;
                case 'rescue':
                    $icon = 'fa-life-ring';
                    break;
                default:
                    $icon = 'fa-headset';
            }

            echo '
          <div class="col-md-6 col-lg-4">
            <div class="card shadow border-0 h-100">
              <div class="card-header text-center py-4" style="background-color: #002855; color: white;">
                <img src="' . $logo_path . '" alt="' . htmlspecialchars($row['emergency_name']) . '" class="mb-2" style="width: 60px; height: 60px; object-fit: contain;">
                <h5 class="mb-0">' . htmlspecialchars($row['emergency_name']) . '</h5>
              </div>
              <div class="card-body bg-white">
                <div class="d-flex align-items-center mb-3">
                  <i class="fas ' . $icon . ' me-3 text-primary" style="font-size: 1.2rem; width: 25px; text-align: center;"></i>
                  <span>' . htmlspecialchars($row['emergency_phone']) . '</span>
                </div>';
            if (!empty($row['emergency_alt_phone'])) {
                echo '
                <div class="d-flex align-items-center mb-3">
                  <i class="fas fa-phone me-3 text-primary" style="font-size: 1.2rem; width: 25px; text-align: center;"></i>
                  <span>' . htmlspecialchars($row['emergency_alt_phone']) . '</span>
                </div>';
            }
            echo '
                <div class="d-flex align-items-center mb-3">
                  <i class="fas fa-map-marker-alt me-3 text-primary" style="font-size: 1.2rem; width: 25px; text-align: center;"></i>
                  <span>' . htmlspecialchars($row['emergency_address']) . '</span>
                </div>';
            if (!empty($row['emergency_notes'])) {
                echo '
                <div class="text-muted small fst-italic mt-3">' . htmlspecialchars($row['emergency_notes']) . '</div>';
            }
            echo '
              </div>
              <div class="card-footer bg-light text-center">
                <span class="badge bg-danger text-white px-3 py-2">' . ucfirst($row['emergency_category']) . '</span>
              </div>
            </div>
          </div>';
        }
        ?>
      </div>
    </div>
  </section>

  <section id="contact-us" class="section bg-light">
  <div class="container">
    <h2 class="section-title text-center">Contact or Message Us</h2>
    <p class="section-subtitle text-center">We’d love to hear from you! Please leave a message, and we’ll respond promptly.</p>

    <form method="POST" action="index.php" id="contactForm" class="mx-auto" style="max-width: 600px;">
      <div class="mb-3">
        <label for="email" class="form-label">Your Email <span class="text-danger">*</span></label>
        <input type="email" class="form-control" id="email" name="email" placeholder="Enter your email" required aria-describedby="emailHelp">
        <div id="emailHelp" class="form-text">We'll never share your email with anyone else.</div>
      </div>
      <div class="mb-3">
        <label for="message" class="form-label">Your Message <span class="text-danger">*</span></label>
        <textarea class="form-control" id="message" name="message" rows="5" placeholder="Write your message here" required></textarea>
      </div>
      <div id="formResponse" class="alert d-none" role="alert"></div>
      <button type="submit" class="btn btn-primary">Send Message</button>
    </form>
  </div>
</section>


  <!-- Footer -->
  <footer class="bg-dark text-white py-5">
    <div class="container">
      <div class="row g-4">
        <div class="col-lg-4 text-center text-lg-start">
          <h5 class="fw-bold mb-3">FOLLOW US</h5>
          <div class="mb-3">
            <a href="#" class="text-white me-3 fs-5"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="text-white me-3 fs-5"><i class="fab fa-twitter"></i></a>
            <a href="#" class="text-white me-3 fs-5"><i class="fab fa-instagram"></i></a>
            <a href="#" class="text-white fs-5"><i class="fab fa-youtube"></i></a>
          </div>
          <div class="mb-3">
            <img src="uploads/Seal.png" alt="Seal of the Philippines" class="img-fluid mb-1" style="max-height: 75px;">
            <p class="small mb-0">Seal of the Philippines</p>
          </div>
          <div>
            <img src="uploads/privacy.png" alt="Data Privacy" class="img-fluid mb-1" style="max-height: 75px;">
            <p class="small mb-0">Data Privacy</p>
          </div>
        </div>

        <div class="col-lg-4">
          <h5 class="fw-bold mb-3">ABOUT</h5>
          <ul class="list-unstyled">
            <li><a href="#" class="text-white text-decoration-none d-block mb-1">Home</a></li>
            <li><a href="#offices" class="text-white text-decoration-none d-block mb-1">Offices</a></li>
            <li><a href="#services" class="text-white text-decoration-none d-block mb-1">Services</a></li>
            <li><a href="#announcements" class="text-white text-decoration-none d-block mb-1">Announcements</a></li>
            <li><a href="#news" class="text-white text-decoration-none d-block mb-1">News</a></li>
            <li><a href="#gallery" class="text-white text-decoration-none d-block mb-1">Gallery</a></li>
            <li><a href="#documents" class="text-white text-decoration-none d-block mb-1">Documents</a></li>
            <li><a href="#emergency" class="text-white text-decoration-none d-block">Emergency</a></li>
          </ul>
        </div>

        <div class="col-lg-4 text-center text-lg-start">
          <h5 class="fw-bold mb-3">LOCAL GOVERNMENT OF<br>LIBMANAN</h5>
          <p>Committed to serving the people of Libmanan with integrity and transparency.</p>
          <img src="uploads/Lib.png" alt="Libmanan Seal" class="img-fluid my-2" style="max-height: 100px;">
          <p class="mb-1">© Municipality of Libmanan 2024</p>
          <a href="#" class="text-white text-decoration-none">Visit Official Website</a>
        </div>
      </div>
    </div>
  </footer>

  <!-- Font Awesome CDN -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <!-- JavaScript Libraries -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>

  <script>
    // Change header background image randomly
    document.addEventListener("DOMContentLoaded", function () {
      const images = [
        'ASSETS/tancong-baka.jpg',
        'ASSETS/bg-lgu.jpg',
        'ASSETS/479689160_986198306769625_2631900909274025359_n.jpg'
      ];
      const header = document.querySelector('header');
      function changeBackgroundImage() {
        const randomImage = images[Math.floor(Math.random() * images.length)];
        header.style.backgroundImage = `url('${randomImage}')`;
      }
      changeBackgroundImage();
      setInterval(changeBackgroundImage, 5000);
    });

    // Navbar scroll effect
    window.addEventListener('scroll', function () {
      const navbar = document.querySelector('.navbar');
      if (window.scrollY > 50) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    });
  </script>

  <!-- JavaScript for News Modal -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const modal = new bootstrap.Modal(document.getElementById('newsModal'));
      const modalTitle = modal._element.querySelector('#newsModalLabel');
      const modalImage = modal._element.querySelector('#modalImage');
      const modalContent = modal._element.querySelector('#modalContent');
      const modalDate = modal._element.querySelector('#modalDate');

      document.querySelectorAll('.news-modal-trigger').forEach(item => {
        item.addEventListener('click', () => {
          modalTitle.textContent = item.getAttribute('data-title');
          modalImage.src = item.getAttribute('data-image');
          modalContent.textContent = item.getAttribute('data-content');
          modalDate.textContent = 'Posted: ' + item.getAttribute('data-date');
        });
      });
    });
  </script>

  <!-- JavaScript for Gallery Modal -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const modal = new bootstrap.Modal(document.getElementById("galleryModal"));
      const modalMediaContainer = document.getElementById("modalMediaContainer");

      document.querySelectorAll(".gallery-clickable").forEach(item => {
        item.addEventListener("click", () => {
          const type = item.getAttribute("data-type");
          modalMediaContainer.innerHTML = "";
          if (type === "image") {
            const src = item.getAttribute("data-src");
            modalMediaContainer.innerHTML = `<img src="${src}" class="img-fluid" alt="Gallery Image">`;
          } else if (type === "video") {
            const src = item.getAttribute("data-src");
            modalMediaContainer.innerHTML = `
              <video controls autoplay class="w-100">
                <source src="${src}" type="video/mp4">
                Your browser does not support the video tag.
              </video>`;
          } else if (type === "link") {
            const embed = item.getAttribute("data-embed");
            modalMediaContainer.innerHTML = embed;
          }
          modal.show();
        });
      });
    });
  </script>

  <!-- JavaScript for Announcement Modal -->
  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const announcementModal = new bootstrap.Modal(document.getElementById("announcementModal"));
      const modalTitle = document.getElementById("announcementModalLabel");
      const modalImage = document.getElementById("announcementModalImage");
      const modalContent = document.getElementById("announcementModalContent");
      const modalDate = document.getElementById("announcementModalDate");

      document.querySelectorAll(".announcement-card").forEach(card => {
        card.addEventListener("click", function () {
          modalTitle.textContent = card.getAttribute("data-title");
          modalImage.src = card.getAttribute("data-image");
          modalContent.textContent = card.getAttribute("data-content");
          modalDate.textContent = "Posted: " + card.getAttribute("data-date");
          announcementModal.show();
        });
      });
    });


    document.addEventListener("DOMContentLoaded", function () {
    const contactForm = document.getElementById('contactForm');
    const formResponse = document.getElementById('formResponse');

    contactForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(contactForm);

        fetch('index.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            formResponse.classList.remove('d-none', 'alert-success', 'alert-danger');
            formResponse.classList.add(data.status === 'success' ? 'alert-success' : 'alert-danger');
            formResponse.textContent = data.message;
            if (data.status === 'success') {
                contactForm.reset();
            }
        })
        .catch(() => {
            formResponse.classList.remove('d-none', 'alert-success', 'alert-danger');
            formResponse.classList.add('alert-danger');
            formResponse.textContent = 'Something went wrong. Please try again later.';
        });
    });
});

  </script>

  <?php
  $conn->close();
  ?>
</body>
</html>
