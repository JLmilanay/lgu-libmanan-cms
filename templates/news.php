<?php
require_once 'config.php'; // Database connection

$admin_mode = (isset($_GET['admin']) && $_GET['admin'] === '1');

// Assuming the office ID is passed as a GET parameter
$office_id = isset($_GET['office_id']) ? intval($_GET['office_id']) : 0;

// Fetch news from the database based on office ID
$news_list = [];
try {
    $stmt = $conn->prepare("SELECT id, title, created_at, content, video_url, image_path, video_file_path 
                            FROM news_content 
                            WHERE office_id = ? 
                            ORDER BY created_at DESC");
    $stmt->bind_param("i", $office_id); // Bind the office ID parameter
    $stmt->execute();
    $result = $stmt->get_result();
    $news_list = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    die("Error fetching news: " . $e->getMessage());
}

// Function to extract YouTube video ID from a URL
function getYoutubeId($url) {
    $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:youtube\.com\/(?:[^\/\n\s]+\/\S+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([a-zA-Z0-9_-]{11})/';
    preg_match($pattern, $url, $matches);
    return isset($matches[1]) ? $matches[1] : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>News Section</title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" />
  <style>
    .container {
      padding-top: 8%;
    }
    .modal-backdrop { 
      z-index: 1190 !important; 
    }
    .modal { 
      z-index: 1200 !important; 
    }
    .section-header {
      text-align: center;
      margin-bottom: 30px;
    }
    .section-header h2 {
      font-size: 2.5rem;
      font-weight: bold;
    }
    .section-header p {
      font-size: 1.1rem;
      color: #555;
    }
    .news-slider {
      display: flex;
      flex-wrap: nowrap;
      overflow-x: auto;
      gap: 15px;
      padding-bottom: 15px;
      scroll-snap-type: x mandatory;
      -webkit-overflow-scrolling: touch;
    }
    .news-slider::-webkit-scrollbar {
      height: 8px;
    }
    .news-slider::-webkit-scrollbar-thumb {
      background-color: rgba(0,0,0,0.2);
      border-radius: 4px;
    }
    .news-slider .news-card-wrapper {
      flex: 0 0 auto;
      scroll-snap-align: start;
      width: 100%;
    }
    @media (min-width: 992px) {
      .news-slider .news-card-wrapper {
        width: calc(33.33% - 10px);
      }
    }
    .card-video-preview {
      width: 100%;
      height: 200px;
      display: block;
      object-fit: cover;
      background-color: #000;
    }
    .card-img-top {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }
    .fullscreen-media {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.85);
      display: none;
      justify-content: center;
      align-items: center;
      z-index: 2050;
    }
    #fullscreenViewer img,
    #fullscreenViewer video,
    #fullscreenViewer iframe {
      max-width: 90%;
      max-height: 90%;
      object-fit: contain;
      border: none;
    }
    .fullscreen-media .btn-close {
      position: absolute;
      top: 20px;
      right: 20px;
      z-index: 2100;
    }
    .news-item {
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .news-item:hover {
      transform: scale(1.03);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    }
    #modalImage {
      width: 100%;
      height: 400px;
      object-fit: cover;
      cursor: pointer;
    }
    #modalVideo {
      width: 100%;
      height: 400px;
      object-fit: cover;
      cursor: pointer;
    }
    #modalEmbed {
      width: 100%;
      height: 400px;
      border: 0;
      display: none;
    }
  </style>
</head>
<body class="bg-light">
  <div class="container my-4">
    <div class="text-center mt-4">
      <h4>
        <?php 
          if (!empty($section['title'])) {
              echo htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8');
          } else {
              echo '<strong style="font-size: 2em; font-weight: bold;"> LATEST NEWS </strong>';
          }
        ?>
      </h4>
      <hr>
      <p class="lead">
          Stay informed with the latest updates and breaking news from our team.
      </p>
    </div>
    
    <div class="news-slider">
      <?php foreach ($news_list as $news): ?>
      <div class="news-card-wrapper">
        <div class="card h-100 news-item"
             data-title="<?php echo htmlspecialchars($news['title']); ?>"
             data-date="<?php echo date('F d, Y', strtotime($news['created_at'])); ?>"
             data-content="<?php echo nl2br(htmlspecialchars($news['content'])); ?>"
             data-image="<?php echo htmlspecialchars($news['image_path']); ?>"
             data-video-url="<?php echo htmlspecialchars($news['video_url']); ?>"
             data-video-file="<?php echo htmlspecialchars($news['video_file_path']); ?>">
          <?php 
            if (!empty($news['video_url'])):
                 $youtubeId = getYoutubeId($news['video_url']);
                 if ($youtubeId) {
                     echo '<img src="https://img.youtube.com/vi/' . htmlspecialchars($youtubeId) . '/hqdefault.jpg" class="card-video-preview" alt="Video Thumbnail">';
                 } else {
                     echo '<iframe src="' . htmlspecialchars($news['video_url']) . '" class="card-video-preview" frameborder="0" allowfullscreen></iframe>';
                 }
            elseif (!empty($news['video_file_path'])):
          ?>
            <video class="card-video-preview" muted playsinline preload="metadata" loop
                   onmouseenter="this.play()" onmouseleave="this.pause()" onclick="event.stopPropagation()">
              <source src="<?php echo htmlspecialchars($news['video_file_path']); ?>" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          <?php 
            elseif (!empty($news['image_path'])):
          ?>
            <img src="<?php echo htmlspecialchars($news['image_path']); ?>" class="card-img-top" alt="News Image">
          <?php endif; ?>
          <div class="card-body">
            <h5 class="card-title"><?php echo htmlspecialchars($news['title']); ?></h5>
            <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($news['content'], 0, 100))) . '...'; ?></p>
          </div>
          <div class="card-footer">
            <small class="text-muted"><?php echo date('F d, Y', strtotime($news['created_at'])); ?></small>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="modal fade" id="newsModal" tabindex="-1" aria-labelledby="newsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
      <div class="modal-content">
        <div class="modal-header">
          <div>
            <h5 class="modal-title" id="newsModalLabel"></h5>
            <p class="mb-0 text-muted" id="newsModalDate"></p>
          </div>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <img id="modalImage" src="" alt="News media">
          <video id="modalVideo" controls style="display: none;"></video>
          <iframe id="modalEmbed" allowfullscreen style="display: none;"></iframe>
          <p id="newsContent" class="mt-3"></p>
        </div>
      </div>
    </div>
  </div>

  <div id="fullscreenViewer" class="fullscreen-media">
    <button type="button" class="btn-close btn-close-white" aria-label="Close" onclick="hideFullscreenMedia()"></button>
    <img id="fullscreenImg" src="" alt="Fullscreen View" style="display: none;">
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let newsModal;
    document.addEventListener("DOMContentLoaded", function() {
      const newsModalEl = document.getElementById('newsModal');
      newsModal = new bootstrap.Modal(newsModalEl);

      document.querySelector('#newsModal .btn-close').addEventListener('click', function(){
          newsModal.hide();
      });

      document.querySelectorAll('.news-item').forEach(function(card) {
        card.addEventListener('click', function() {
          const title = card.dataset.title;
          const date = card.dataset.date;
          const content = card.dataset.content;
          const image = card.dataset.image;
          const videoURL = card.dataset.videoUrl;
          const videoFile = card.dataset.videoFile;
          
          document.getElementById('newsModalLabel').textContent = title;
          document.getElementById('newsModalDate').textContent  = date;
          document.getElementById('newsContent').innerHTML  = content;
          
          const modalImage = document.getElementById('modalImage');
          const modalVideo = document.getElementById('modalVideo');
          const modalEmbed = document.getElementById('modalEmbed');
          
          modalImage.style.display = 'none';
          modalVideo.style.display = 'none';
          modalEmbed.style.display = 'none';
          modalVideo.pause();
          modalVideo.currentTime = 0;
          modalVideo.src = '';
          modalEmbed.src = '';
          
          if (videoFile && videoFile.trim() !== "") {
            modalVideo.src = videoFile;
            modalVideo.style.display = 'block';
          } else if (videoURL && videoURL.trim() !== "") {
            let processedURL = videoURL;
            if (videoURL.indexOf("youtu.be") !== -1) {
              let parts = videoURL.split("youtu.be/");
              if(parts.length > 1){
                let idPart = parts[1].split('?')[0];
                processedURL = "https://www.youtube.com/embed/" + idPart + "?autoplay=1";
              }
            } else if (videoURL.indexOf("youtube.com") !== -1) {
              try {
                let urlObj = new URL(videoURL);
                let vParam = urlObj.searchParams.get("v");
                if (vParam) {
                  processedURL = "https://www.youtube.com/embed/" + vParam + "?autoplay=1";
                }
              } catch(e) { }
            }
            modalEmbed.src = processedURL;
            modalEmbed.style.display = 'block';
          } else if (image && image.trim() !== "") {
            modalImage.src = image;
            modalImage.style.display = 'block';
          }
          
          newsModal.show();
        });
      });
      
      document.getElementById('newsModal').addEventListener('hidden.bs.modal', function () {
         const modalVideo = document.getElementById('modalVideo');
         const modalEmbed = document.getElementById('modalEmbed');
         modalVideo.pause();
         modalVideo.currentTime = 0;
         modalVideo.src = '';
         modalEmbed.src = '';
      });
    });
    
    function hideFullscreenMedia() {
      const viewer = document.getElementById("fullscreenViewer");
      viewer.style.display = "none";
      document.getElementById("fullscreenImg").style.display = "none";
    }
    
    function showFullscreenImage(img) {
      const viewer = document.getElementById("fullscreenViewer");
      const fullImg = document.getElementById("fullscreenImg");
      fullImg.src = img.src;
      fullImg.style.display = "block";
      viewer.style.display = "flex";
    }
    
    document.getElementById('modalImage').addEventListener('click', function() {
      showFullscreenImage(this);
    });
  </script>
</body>
</html>