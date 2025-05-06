<?php
// Database Connection
include 'config.php';
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
        #awardsCarousel{
            padding-top: 10%;
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
            font-family: 'Playfair Display', serif;
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
        
        .nav-link:hover, .nav-link.active {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }
        
        /* Section Styles */
        .section {
            padding: 80px 0;
            position: relative;
        }
        
        .section-title {
            font-family: 'Playfair Display', serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 60px;
            height: 3px;
            background: var(--secondary-color);
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
        
        .card-body {
            padding: 20px;
            flex-grow: 1;
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

    <!-- Header Section -->
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
                        <img src="'.$image_path.'" class="card-img-top" alt="'.htmlspecialchars($row['office_name']).'">
                        <div class="card-body">
                            <h5 class="card-title">'.htmlspecialchars($row['office_name']).'</h5>
                            <p class="card-text">'.htmlspecialchars(substr($row['office_description'], 0, 100)).'...</p>
                            <a href="'.$row['office_link'].'" class="btn btn-outline-primary">Learn More</a>
                        </div>
                    </div>
                </div>';
            }
            ?>
        </div>

        <div class="text-center mt-4">
            <!-- Button to trigger modal -->
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#allOfficesModal">View All Offices</button>
        </div>
    </div>
</section>

<!-- Modal for All Offices -->
 <!-- Make sure Bootstrap JS is included -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
                      <img src="'.$image_path.'" class="card-img-top" alt="'.htmlspecialchars($row['office_name']).'">
                      <div class="card-body">
                          <h5 class="card-title">'.htmlspecialchars($row['office_name']).'</h5>
                          <p class="card-text">'.htmlspecialchars(substr($row['office_description'], 0, 100)).'...</p>
                          <a href="'.$row['office_link'].'" class="btn btn-outline-primary">Learn More</a>
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

        <!-- Container for Infinite Scrolling -->
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
                                <img src="'.$icon_path.'" alt="'.htmlspecialchars($row['service_name']).'" class="img-fluid mb-3" style="height: 60px;">
                                <h5 class="card-title">'.htmlspecialchars($row['service_name']).'</h5>
                                <p class="card-text">'.htmlspecialchars(substr($row['service_description'], 0, 120)).'...</p>
                                <small class="text-muted">Offered by: '.htmlspecialchars($row['office_name']).'</small>
                            </div>
                        </div>
                    </div>';
                }
                ?>
            </div>
        </div>
    </div>
</section>

<!-- Add Custom CSS for Infinite Scrolling -->
<style>
/* Wrapper for the carousel to contain it and hide overflow */
.services-wrapper {
    overflow: hidden;
    width: 100%;
}

/* The actual scrolling container */
.services-carousel {
    display: flex;
    animation: scrollInfinite 20s linear infinite;
}

/* The individual service items */
.service-item {
    flex: 0 0 auto;
    width: 300px;  /* Set the width of each item */
    margin-right: 30px;  /* Adjust spacing between items */
}

/* Infinite scroll animation */
@keyframes scrollInfinite {
    0% {
        transform: translateX(0);
    }
    100% {
        transform: translateX(-100%);
    }
}

/* Make the items appear seamless (e.g. duplicate them to create an illusion of continuous loop) */
.services-carousel::after {
    content: "";
    flex: 0 0 0;
}
</style>

<!-- Optional JavaScript for smoother effect (if needed) -->
<script>
// Optional: To ensure seamless looping, duplicate the items inside the carousel
document.addEventListener("DOMContentLoaded", function () {
    const carousel = document.querySelector('.services-carousel');
    const items = carousel.innerHTML; // Get the carousel items
    carousel.innerHTML += items; // Append the same items for seamless scrolling
});
</script>


<!-- Announcements Section -->
<section id="announcements" class="section">
    <div class="container">
        <h2 class="section-title text-center">Announcements</h2>
        <p class="section-subtitle text-center">Stay informed with the latest updates and public advisories from the Local Government Unit.</p>
        
        <div class="row g-4">
            <?php
            $result = $conn->query("SELECT announcement_id, announcement_title, announcement_content, announcement_image_url, announcement_date 
                                   FROM lgu_main_announcements 
                                   WHERE announcement_status = 'published' 
                                   ORDER BY announcement_date DESC 
                                   LIMIT 3");
            while ($row = $result->fetch_assoc()) {
                $image_path = ltrim($row['announcement_image_url'], './');
                $date = date("F j, Y", strtotime($row['announcement_date']));
                echo '
                <div class="col-md-4">
                    <div class="card h-100">
                        <img src="'.$image_path.'" class="card-img-top" alt="'.htmlspecialchars($row['announcement_title']).'">
                        <div class="card-body">
                            <h5 class="card-title">'.htmlspecialchars($row['announcement_title']).'</h5>
                            <p class="card-text">'.htmlspecialchars(substr($row['announcement_content'], 0, 100)).'...</p>
                            <small class="text-muted">Posted: '.$date.'</small>
                        </div>
                    </div>
                </div>';
            }
            ?>
        </div>

        <div class="text-center mt-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#allAnnouncementsModal">View All Announcements</button>
        </div>
    </div>
</section>

<!-- Modal: All Announcements -->
<div class="modal fade" id="allAnnouncementsModal" tabindex="-1" aria-labelledby="allAnnouncementsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">All Announcements</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-4">
          <?php
          $all_announcements = $conn->query("SELECT announcement_id, announcement_title, announcement_content, announcement_image_url, announcement_date 
                                             FROM lgu_main_announcements 
                                             WHERE announcement_status = 'published' 
                                             ORDER BY announcement_date DESC");
          while ($row = $all_announcements->fetch_assoc()) {
              $image_path = ltrim($row['announcement_image_url'], './');
              $date = date("F j, Y", strtotime($row['announcement_date']));
              echo '
              <div class="col-md-6 col-lg-4">
                  <div class="card h-100 announcement-card" 
                      data-bs-toggle="modal" 
                      data-bs-target="#announcementFullModal"
                      data-title="'.htmlspecialchars($row['announcement_title']).'"
                      data-content="'.htmlspecialchars($row['announcement_content']).'"
                      data-image="'.$image_path.'"
                      data-date="'.$date.'">
                      <img src="'.$image_path.'" class="card-img-top" alt="'.htmlspecialchars($row['announcement_title']).'">
                      <div class="card-body">
                          <h5 class="card-title">'.htmlspecialchars($row['announcement_title']).'</h5>
                          <p class="card-text">'.htmlspecialchars(substr($row['announcement_content'], 0, 100)).'...</p>
                          <small class="text-muted">Posted: '.$date.'</small>
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

<!-- Modal: Full Announcement View -->
<div class="modal fade" id="announcementFullModal" tabindex="-1" aria-labelledby="announcementFullModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="announcementFullModalLabel"></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <img src="" id="announcementImage" class="img-fluid mb-3" alt="">
        <p><small class="text-muted" id="announcementDate"></small></p>
        <p id="announcementContent"></p>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript to handle dynamic content -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const fullModalEl = document.getElementById('announcementFullModal');
    const fullModal = new bootstrap.Modal(fullModalEl);
    const allModalEl = document.getElementById('allAnnouncementsModal');
    const allModal = new bootstrap.Modal(allModalEl);

    // Show full modal
    document.querySelectorAll(".announcement-card").forEach(card => {
        card.addEventListener("click", () => {
            document.getElementById("announcementFullModalLabel").textContent = card.dataset.title;
            document.getElementById("announcementContent").textContent = card.dataset.content;
            document.getElementById("announcementImage").src = card.dataset.image;
            document.getElementById("announcementDate").textContent = "Posted: " + card.dataset.date;

            const allModalInstance = bootstrap.Modal.getInstance(allModalEl);
            allModalInstance.hide();

            fullModal.show();
        });
    });

    fullModalEl.addEventListener('hidden.bs.modal', () => {
        allModal.show();
    });
});
</script>

<!-- Custom CSS -->
<style>
/* Center and scale image inside modal */
#announcementImage {
    display: block;
    max-width: 100%;
    max-height: 500px;
    object-fit: contain;
    margin-left: auto;
    margin-right: auto;
}

/* Properly wrap long text */
#announcementContent {
    white-space: pre-wrap;
    word-wrap: break-word;
    overflow-wrap: break-word;
    font-size: 1.05rem;
    line-height: 1.6;
}

/* Optional: More spacing inside modal */
.modal-body {
    padding: 1.5rem;
}

/* Optional: Reduce modal backdrop darkness */
.modal-backdrop.show {
    opacity: 0.3;
}
</style>


<!-- News Section -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<section id="news" class="section bg-light py-5">
  <div class="container">
    <h2 class="section-title text-center">Latest News</h2>
    <p class="section-subtitle text-center">Stay updated on community projects, government initiatives, and local events.</p>

    <div id="newsCarousel" class="carousel slide" data-bs-ride="carousel">
      <div class="carousel-inner rounded">
        <?php
        // Fetch latest 5 published news
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
            <div class="carousel-item '.($first ? 'active' : '').'">
                <img src="'.$image_path.'" 
                     class="d-block w-100 img-fluid rounded news-modal-trigger" 
                     alt="'.htmlspecialchars($row['news_title']).'" 
                     data-bs-toggle="modal" 
                     data-bs-target="#newsModal"
                     data-title="'.htmlspecialchars($row['news_title']).'"
                     data-content="'.htmlspecialchars($row['news_content']).'"
                     data-image="'.$image_path.'"
                     data-date="'.$date.'">
                <div class="carousel-caption">
                    <div class="carousel-caption-inner">
                        <h5>'.htmlspecialchars($row['news_title']).'</h5>
                        <p>'.htmlspecialchars(substr($row['news_content'], 0, 100)).'...</p>
                        <small>Posted: '.$date.'</small>
                    </div>
                </div>
            </div>';
            $first = false;
        }
        ?>
      </div>

      <!-- Carousel Controls -->
      <button class="carousel-control-prev" type="button" data-bs-target="#newsCarousel" data-bs-slide="prev">
        <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#newsCarousel" data-bs-slide="next">
        <span class="carousel-control-next-icon" aria-hidden="true"></span>
      </button>
    </div>
  </div>
</section>

<!-- Modal -->
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

<style>
.carousel-caption {
  position: absolute;
  bottom: 0;
  left: 0;
  width: 100%;
  background: linear-gradient(to top, rgba(0, 0, 0, 0.95) 0%, rgba(0, 0, 0, 0.6) 50%, rgba(255, 255, 255, 0) 100%);
  color: white;
  text-align: center;
  padding: 1rem 0.5rem;
}

.carousel-caption-inner {
  z-index: 2;
  margin: 0 auto;
}

.carousel-caption h5 {
  font-size: 1.5rem;
  font-weight: 100;
  color: #fff;
}

.carousel-caption p {
  font-size: 1.25rem;
  font-weight: 500;
  color: #eee;
}

.carousel-caption small {
  font-size: 1rem;
  color: #ccc;
}

.carousel-inner .carousel-item img {
  height: 500px;
  object-fit: cover;
  border-radius: 8px;
  cursor: pointer;
}

.carousel-control-prev-icon,
.carousel-control-next-icon {
  background-color: rgba(0, 0, 0, 0.5);
  padding: 1.5rem;
  border-radius: 50%;
}

/* Modal Fixes */
#modalImage {
  display: block;
  max-width: 100%;
  max-height: 700px;
  object-fit: contain;
  margin-left: auto;
  margin-right: auto;
}

.modal-body {
  max-height: 70vh;
  overflow-y: auto;
}

#modalContent {
  white-space: pre-wrap;
  word-wrap: break-word;
  overflow-wrap: break-word;
  font-size: 1.2rem;
  line-height: 1.6;
}

@media (max-width: 768px) {
  .carousel-caption h5 { font-size: 1.5rem; }
  .carousel-caption p { font-size: 1rem; }
  .carousel-caption small { font-size: 0.875rem; }
}
</style>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Modal Script -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = document.getElementById('newsModal');
  const modalTitle = modal.querySelector('#newsModalLabel');
  const modalImage = modal.querySelector('#modalImage');
  const modalContent = modal.querySelector('#modalContent');
  const modalDate = modal.querySelector('#modalDate');

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

<!-- Gallery Section -->
<section id="gallery" class="section">
    <div class="container">
        <h2 class="section-title text-center">Gallery</h2>
        <p class="section-subtitle text-center">
            Explore moments from various events, programs, and initiatives.
        </p>

        <div class="row g-3">
            <?php
            $result = $conn->query("SELECT gallery_image_url, gallery_caption, gallery_type 
                                   FROM lgu_main_gallery 
                                   WHERE gallery_status = 'active' 
                                   ORDER BY gallery_date DESC 
                                   LIMIT 8");
            while ($row = $result->fetch_assoc()) {
                $media_path = ltrim($row['gallery_image_url'], './');
                $media_tag = ($row['gallery_type'] == 'image') 
                    ? '<img src="'.$media_path.'" class="card-img-top gallery-clickable" data-type="image" data-src="'.$media_path.'" alt="'.htmlspecialchars($row['gallery_caption']).'">'
                    : '<video class="card-img-top gallery-clickable" muted data-type="video" data-src="'.$media_path.'">
                        <source src="'.$media_path.'" type="video/mp4">
                       </video>';

                echo '
                <div class="col-6 col-md-4 col-lg-3">
                    <div class="card gallery-item">
                        '.$media_tag.'
                        <div class="card-body">
                            <p class="card-text small">'.htmlspecialchars($row['gallery_caption']).'</p>
                        </div>
                    </div>
                </div>';
            }
            ?>
        </div>

        <div class="text-center mt-4">
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#fullGalleryModal">View Full Gallery</button>
        </div>
    </div>
</section>

<!-- Modal for Single Item Preview -->
<div class="modal fade" id="galleryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-dark text-white">
      <div class="modal-body text-center">
        <span id="modalMediaContainer"></span>
      </div>
      <div class="modal-footer justify-content-center">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal for Full Gallery -->
<div class="modal fade" id="fullGalleryModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Full Gallery</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <?php
          $full_result = $conn->query("SELECT gallery_image_url, gallery_caption, gallery_type 
                                       FROM lgu_main_gallery 
                                       WHERE gallery_status = 'active' 
                                       ORDER BY gallery_date DESC");
          while ($row = $full_result->fetch_assoc()) {
              $image_path = ltrim($row['gallery_image_url'], './');
              $media_tag = ($row['gallery_type'] == 'image') 
                  ? '<img src="'.$image_path.'" class="img-fluid gallery-clickable" data-type="image" data-src="'.$image_path.'" alt="'.htmlspecialchars($row['gallery_caption']).'">'
                  : '<video class="img-fluid gallery-clickable" muted data-type="video" data-src="'.$image_path.'">
                      <source src="'.$image_path.'" type="video/mp4">
                     </video>';

              echo '
              <div class="col-6 col-md-4 col-lg-3">
                <div class="card gallery-item">
                    '.$media_tag.'
                    <div class="card-body">
                        <p class="card-text small">'.htmlspecialchars($row['gallery_caption']).'</p>
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

<!-- JavaScript for Modal Behavior -->
<script>
document.addEventListener("DOMContentLoaded", function () {
    const modal = new bootstrap.Modal(document.getElementById("galleryModal"));
    const modalMediaContainer = document.getElementById("modalMediaContainer");

    document.querySelectorAll(".gallery-clickable").forEach(item => {
        item.addEventListener("click", () => {
            const src = item.getAttribute("data-src");
            const type = item.getAttribute("data-type");

            modalMediaContainer.innerHTML = "";

            if (type === "image") {
                modalMediaContainer.innerHTML = `<img src="${src}" class="img-fluid" alt="Gallery Image">`;
            } else if (type === "video") {
                modalMediaContainer.innerHTML = `
                    <video controls autoplay class="w-100">
                        <source src="${src}" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>`;
            }

            modal.show();
        });
    });
});
</script>

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
                    $file_path = 'uploads/'.ltrim($row['document_url'], './');
                    $file_icon = $row['document_type'] == 'pdf' ? 'fa-file-pdf' : 'fa-file-word';
                    echo '
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas '.$file_icon.' fa-3x text-danger me-3"></i>
                                    <div>
                                        <h5 class="card-title mb-1">'.$row['document_title'].'</h5>
                                        <small class="text-muted">'.strtoupper($row['document_type']).' Document</small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent">
                                <a href="'.$file_path.'" class="btn btn-sm btn-outline-primary w-100" download>
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
            include 'config.php'; // Database connection

            $result = $conn->query("SELECT emergency_name, emergency_phone, emergency_alt_phone, emergency_address, emergency_logo, emergency_category, emergency_notes 
                                    FROM lgu_main_emergency 
                                    WHERE emergency_status = 'active' 
                                    ORDER BY emergency_category");

            while ($row = $result->fetch_assoc()) {
                $logo_path = ltrim($row['emergency_logo'], './');

                // Icon based on category
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
                            <img src="'.$logo_path.'" alt="'.htmlspecialchars($row['emergency_name']).'" class="mb-2" style="width: 60px; height: 60px; object-fit: contain;">
                            <h5 class="mb-0">'.htmlspecialchars($row['emergency_name']).'</h5>
                        </div>
                        <div class="card-body bg-white">
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas '.$icon.' me-3 text-primary" style="font-size: 1.2rem; width: 25px; text-align: center;"></i>
                                <span>'.htmlspecialchars($row['emergency_phone']).'</span>
                            </div>';

                if (!empty($row['emergency_alt_phone'])) {
                    echo '
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-phone me-3 text-primary" style="font-size: 1.2rem; width: 25px; text-align: center;"></i>
                                <span>'.htmlspecialchars($row['emergency_alt_phone']).'</span>
                            </div>';
                }

                echo '
                            <div class="d-flex align-items-center mb-3">
                                <i class="fas fa-map-marker-alt me-3 text-primary" style="font-size: 1.2rem; width: 25px; text-align: center;"></i>
                                <span>'.htmlspecialchars($row['emergency_address']).'</span>
                            </div>';

                if (!empty($row['emergency_notes'])) {
                    echo '
                            <div class="text-muted small fst-italic mt-3">'.htmlspecialchars($row['emergency_notes']).'</div>';
                }

                echo '
                        </div>
                        <div class="card-footer bg-light text-center">
                            <span class="badge bg-danger text-white px-3 py-2">'.ucfirst($row['emergency_category']).'</span>
                        </div>
                    </div>
                </div>';
            }
            ?>
        </div>
    </div>
</section>


<!-- Footer -->
<footer class="bg-dark text-white py-5">
  <div class="container">
    <div class="row g-4">
      
      <!-- Social Media & Seals -->
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

      <!-- About Links -->
      <div class="col-lg-4">
        <h5 class="fw-bold mb-3">ABOUT</h5>
        <ul class="list-unstyled">
        <li><a href="#" class="text-white text-decoration-none d-block mb-1">Home</a></li>
          <li><a href="#offices" class="text-white text-decoration-none d-block mb-1">Office</a></li>
          <li><a href="#services" class="text-white text-decoration-none d-block mb-1">Services</a></li>
          <li><a href="#announcements" class="text-white text-decoration-none d-block mb-1">Announcements</a></li>
          <li><a href="#news" class="text-white text-decoration-none d-block mb-1">News</a></li>
          <li><a href="#gallery" class="text-white text-decoration-none d-block mb-1">Gallery</a></li>
          <li><a href="#documents" class="text-white text-decoration-none d-block mb-1">Document</a></li>
          <li><a href="#emergency" class="text-white text-decoration-none d-block">Emergency</a></li>
        </ul>
      </div>

      <!-- Government Branding -->
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

<!-- Font Awesome CDN (for icons) -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    
    <script>
        // Initialize announcement modal
        var announcementModal = document.getElementById('announcementModal');
        announcementModal.addEventListener('show.bs.modal', function(event) {
            var button = event.relatedTarget;
            var title = button.getAttribute('data-title');
            var content = button.getAttribute('data-content');
            var image = button.getAttribute('data-image');
            var date = button.getAttribute('data-date');
            
            document.getElementById('announcementModalTitle').textContent = title;
            document.getElementById('announcementModalContent').textContent = content;
            document.getElementById('announcementModalImage').src = image;
            document.getElementById('announcementModalDate').textContent = 'Posted on: ' + date;
        });
        
        // Change header background
        document.addEventListener("DOMContentLoaded", function() {
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
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>