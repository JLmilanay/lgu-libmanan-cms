<?php
// templates/announcements.php
// Elegant design with sophisticated text handling

if (!isset($GLOBALS['announcement_modal_loaded'])) {
    $GLOBALS['announcement_modal_loaded'] = true;
    ?>
    <!-- Announcement Template CSS -->
    <style>
      /* Full-width section with subtle elegance */
      #announcements {
        background-color: #fafafa;
        padding: 80px 0;
        width: 100%;
      }

      /* Centered content container */
      .announcements-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 40px;
      }

      /* Sophisticated header styling */
      .announcements-header {
        margin-bottom: 60px;
        text-align: center;
      }
      .announcements-header h2 {
        font-weight: 700;
        font-size: 3.2em;
        margin: 0;
        color: #222;
        letter-spacing: -0.5px;
        font-family: 'Helvetica Neue', Arial, sans-serif;
      }
      .announcements-header p {
        font-size: 1.4em;
        margin: 20px 0 0;
        color: #666;
        font-weight: 300;
        line-height: 1.5;
      }

      /* Refined cards container */
      .announcements-scroll-container {
        display: flex;
        overflow-x: auto;
        padding: 10px 0 30px;
        scroll-behavior: smooth;
        -webkit-overflow-scrolling: touch;
      }
      /* Hide scrollbar for cleaner look */
      .announcements-scroll-container::-webkit-scrollbar {
        display: block;
      }

      /* Elegant card design */
      .announcement-card {
        flex: 0 0 360px;
        margin: 0 15px;
        border-radius: 12px;
        background-color: #fff;
        box-shadow: 0 5px 25px rgba(0, 0, 0, 0.08);
        cursor: pointer;
        transition: all 0.3s ease;
        height: 460px;
        position: relative;
        overflow: hidden;
        border: 1px solid rgba(0,0,0,0.05);
      }
      .announcement-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
      }
      
      /* Card image with subtle hover effect */
      .card-image {
        width: 100%;
        height: 220px;
        object-fit: cover;
        border-top-left-radius: 12px;
        border-top-right-radius: 12px;
        transition: transform 0.4s ease;
      }
      .announcement-card:hover .card-image {
        transform: scale(1.02);
      }
      
      /* Refined card content */
      .card-content {
        padding: 25px;
        height: 240px;
        display: flex;
        flex-direction: column;
      }
      .card-title {
        font-size: 1.5em;
        margin: 0 0 18px 0;
        line-height: 1.3;
        color: #222;
        font-weight: 600;
        font-family: 'Helvetica Neue', Arial, sans-serif;
        /* Removed overflow handling for title */
      }
      .card-caption {
        font-size: 1.1em;
        color: #555;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 5;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 15px;
        flex-grow: 1;
      }
      .read-more {
        color: #2a7ae2;
        font-weight: 500;
        font-size: 0.95em;
        margin-top: auto;
        align-self: flex-start;
        transition: all 0.2s ease;
        padding: 8px 12px;
        background-color: rgba(255, 255, 255, 0.9);
        border-radius: 20px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        border: 1px solid rgba(0, 0, 0, 0.05);
      }
      .announcement-card:hover .read-more {
        transform: translateX(3px);
        background-color: white;
      }

      /* Sleek modal styling */
      .modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.85);
        justify-content: center;
        align-items: center;
        z-index: 1000;
      }
      .modal.active {
        display: flex;
      }
      .modal-content {
        background: #fff;
        border-radius: 12px;
        max-width: 800px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
        position: relative;
        padding: 50px;
        box-shadow: 0 30px 80px rgba(0,0,0,0.3);
      }
      .modal-image {
        width: 100%;
        max-height: 400px;
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 30px;
        cursor: zoom-in;
        transition: transform 0.3s;
      }
      .modal-image:hover {
        transform: scale(1.01);
      }
      .modal-title {
        margin: 0 0 25px 0;
        font-size: 2em;
        font-weight: 700;
        color: #222;
        line-height: 1.3;
      }
      .modal-caption {
        font-size: 1.15em;
        color: #555;
        line-height: 1.7;
      }
      
      /* Full image modal */
      .full-image-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.95);
        justify-content: center;
        align-items: center;
        z-index: 1100;
      }
      .full-image-modal.active {
        display: flex;
      }
      .full-image-content {
        max-width: 90%;
        max-height: 90%;
      }
      .full-image {
        max-width: 100%;
        max-height: 90vh;
        object-fit: contain;
      }
      
      /* Minimal close button */
      .close-button {
        position: absolute;
        top: 25px;
        right: 25px;
        font-size: 28px;
        color: #999;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s ease;
      }
      .close-button:hover {
        color: #333;
      }
      .full-image-modal .close-button {
        color: rgba(255,255,255,0.7);
      }
      .full-image-modal .close-button:hover {
        color: #fff;
      }

      /* Responsive refinements */
      @media (max-width: 1200px) {
        .announcement-card {
          flex: 0 0 340px;
          height: 440px;
        }
        .card-image {
          height: 200px;
        }
      }

      @media (max-width: 768px) {
        #announcements {
          padding: 60px 0;
        }
        .announcements-container {
          padding: 0 25px;
        }
        .announcements-header h2 {
          font-size: 2.4em;
        }
        .announcements-header p {
          font-size: 1.2em;
        }
        .announcement-card {
          flex: 0 0 300px;
          height: 420px;
          margin: 0 10px;
        }
        .read-more {
          padding: 6px 10px;
          font-size: 0.9em;
        }
      }

      @media (max-width: 480px) {
        .announcements-header h2 {
          font-size: 2em;
        }
        .modal-content {
          padding: 40px 25px;
          width: 95%;
        }
        .modal-title {
          font-size: 1.7em;
        }
      }
    </style>
    
    <!-- Global Announcement Modal -->
    <div class="modal" id="announcementModal">
      <div class="modal-content">
        <span class="close-button" id="announcementModalClose">&times;</span>
        <img src="" alt="" class="modal-image" id="announcementModalImage">
        <h2 class="modal-title" id="announcementModalTitle"></h2>
        <p class="modal-caption" id="announcementModalCaption"></p>
      </div>
    </div>
    
    <!-- Global Full-Size Image Modal -->
    <div class="full-image-modal" id="announcementFullImageModal">
      <div class="full-image-content">
        <span class="close-button" id="announcementFullImageClose">&times;</span>
        <img src="" alt="Full Image" class="full-image" id="announcementFullImage">
      </div>
    </div>
    
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Open announcement modal
        function openAnnouncementModal(card) {
          var title   = card.getAttribute('data-title');
          var caption = card.getAttribute('data-caption');
          var image   = card.getAttribute('data-image');
          
          document.getElementById('announcementModalTitle').textContent = title;
          document.getElementById('announcementModalCaption').innerHTML = caption.replace(/\n/g, '<br>');
          document.getElementById('announcementModalImage').setAttribute('src', image);
          document.getElementById('announcementModal').classList.add('active');
          document.body.style.overflow = 'hidden';
        }
        
        // Bind click events to cards
        var cards = document.querySelectorAll('.announcement-card');
        cards.forEach(function(card) {
          card.addEventListener('click', function() {
            openAnnouncementModal(card);
          });
        });
        
        // Close main modal
        document.getElementById('announcementModalClose').addEventListener('click', function() {
          document.getElementById('announcementModal').classList.remove('active');
          document.body.style.overflow = '';
        });
        
        // Close modal when clicking outside
        document.getElementById('announcementModal').addEventListener('click', function(event) {
          if (event.target === this) {
            this.classList.remove('active');
            document.body.style.overflow = '';
          }
        });
        
        // Open full-size image modal
        document.getElementById('announcementModalImage').addEventListener('click', function(e) {
          e.stopPropagation();
          var src = this.getAttribute('src');
          document.getElementById('announcementFullImage').setAttribute('src', src);
          document.getElementById('announcementFullImageModal').classList.add('active');
          document.getElementById('announcementModal').classList.remove('active');
        });
  
        // Close full-size image modal
        document.getElementById('announcementFullImageClose').addEventListener('click', function() {
          document.getElementById('announcementFullImageModal').classList.remove('active');
          document.body.style.overflow = '';
        });
        
        // Close full-size modal when clicking outside
        document.getElementById('announcementFullImageModal').addEventListener('click', function(event) {
          if (event.target === this) {
            this.classList.remove('active');
            document.body.style.overflow = '';
          }
        });
      });
    </script>
    <?php
}

global $conn, $office_id;
$query  = "SELECT * FROM announcements_content WHERE office_id = " . intval($office_id) . " ORDER BY id DESC";
$result = $conn->query($query);
?>

<!-- Announcements Section -->
<section id="announcements">
  <div class="announcements-container">
    <!-- Section Heading and Description -->
    <!-- About Title and Description -->
    <div class="text-center mt-4">
      <h4>
        <?php 
          if (!empty($section['title'])) {
              echo htmlspecialchars($section['title'], ENT_QUOTES, 'UTF-8');
          } else {
              echo '<strong style="font-size: 2em; font-weight: bold;">ANNOUNCEMENTS</strong>';
          }
        ?>
      </h4>
      <hr>
      <p class="lead">
        Stay informed with the latest update and news.
      </p>
    </div>

    <!-- Announcements Cards Container -->
    <div class="announcements-scroll-container">
      <?php while ($announcement = $result->fetch_assoc()):
        $backgroundColor = !empty($announcement['background_color']) ? htmlspecialchars($announcement['background_color']) : '#fff';
      ?>
        <div class="announcement-card"
             style="background-color: <?php echo $backgroundColor; ?>;"
             data-title="<?php echo htmlspecialchars($announcement['title']); ?>"
             data-caption="<?php echo htmlspecialchars($announcement['caption']); ?>"
             data-image="<?php echo htmlspecialchars($announcement['image_path']); ?>">
          <?php if (!empty($announcement['image_path'])): ?>
            <img src="<?php echo htmlspecialchars($announcement['image_path']); ?>" alt="<?php echo htmlspecialchars($announcement['title']); ?>" class="card-image">
          <?php endif; ?>
          <div class="card-content">
            <h2 class="card-title"><?php echo htmlspecialchars($announcement['title']); ?></h2>
            <p class="card-caption"><?php echo nl2br(htmlspecialchars($announcement['caption'])); ?></p>
            <span class="read-more">Read more →</span>
          </div>
        </div>
      <?php endwhile; ?>
    </div>
  </div>
</section>