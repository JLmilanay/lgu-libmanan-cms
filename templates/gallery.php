<?php
require_once 'config.php'; // Include your database configuration

// Thumbnail configuration constants
define('THUMBNAIL_DIR', 'uploads/thumbnails/');
define('THUMBNAIL_QUALITY', 3); // 2-31 where lower is better
define('THUMBNAIL_WIDTH', 640); // Width to resize thumbnails to
define('FFMPEG_PATH', trim(shell_exec('which ffmpeg') ?: 'ffmpeg')); // Auto-detect or use 'ffmpeg'
define('DEFAULT_THUMBNAIL', 'assets/images/default-thumbnail.jpg');
define('DEFAULT_VIDEO_THUMBNAIL', 'assets/images/default-video-thumbnail.jpg');

// --------------------- Video Helper Functions --------------------- //
if (!function_exists('getYouTubeId')) {
    function getYouTubeId($url) {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $params);
        if (!empty($params['v'])) {
            return $params['v'];
        }
        if (preg_match('/youtu\.be\/([^\?]+)/', $url, $matches)) {
            return $matches[1];
        }
        return false;
    }
}

if (!function_exists('getEmbedUrl')) {
    function getEmbedUrl($url) {
        // Facebook
        if (stripos($url, 'facebook.com') !== false || stripos($url, 'fb.watch') !== false) {
            return "https://www.facebook.com/plugins/video.php?href=" . urlencode($url) . "&show_text=0&width=560";
        }
        // YouTube
        $ytId = getYouTubeId($url);
        if ($ytId) {
            return "https://www.youtube.com/embed/{$ytId}?autoplay=1&rel=0&modestbranding=1";
        }
        // TikTok
        if (stripos($url, 'tiktok.com') !== false) {
            return "https://www.tiktok.com/embed/v2/" . basename($url);
        }
        // Vimeo
        if (stripos($url, 'vimeo.com') !== false) {
            $vimeoId = basename($url);
            return "https://player.vimeo.com/video/{$vimeoId}?autoplay=1&title=0&byline=0";
        }
        return $url;
    }
}

if (!function_exists('generateVideoThumbnail')) {
    function generateVideoThumbnail($videoPath) {
        // Create thumbnail directory if it doesn't exist
        if (!file_exists(THUMBNAIL_DIR)) {
            if (!@mkdir(THUMBNAIL_DIR, 0755, true)) {
                error_log("Failed to create thumbnail directory: " . THUMBNAIL_DIR);
                return DEFAULT_VIDEO_THUMBNAIL;
            }
        }
        
        // Generate unique filename
        $thumbnailPath = THUMBNAIL_DIR . md5($videoPath) . '.jpg';
        
        // Return existing thumbnail if valid
        if (file_exists($thumbnailPath)) {
            $imageInfo = @getimagesize($thumbnailPath);
            if ($imageInfo !== false) {
                return $thumbnailPath;
            }
            @unlink($thumbnailPath); // Remove corrupt thumbnail
        }
        
        // Verify video file exists and is readable
        if (!file_exists($videoPath) || !is_readable($videoPath)) {
            error_log("Video file not accessible: " . $videoPath);
            return DEFAULT_VIDEO_THUMBNAIL;
        }
        
        // Try FFMPEG if available
        if (!empty(FFMPEG_PATH) && function_exists('shell_exec')) {
            // Get video duration
            $durationCommand = FFMPEG_PATH . " -i " . escapeshellarg($videoPath) . " 2>&1 | grep Duration | awk '{print $2}' | tr -d ,";
            $duration = @shell_exec($durationCommand);
            
            $positions = [];
            
            // Calculate seek positions based on duration if available
            if ($duration && preg_match('/(\d+):(\d+):(\d+)/', $duration, $matches)) {
                $totalSeconds = ($matches[1] * 3600) + ($matches[2] * 60) + $matches[3];
                // Try at 10%, 30%, and 50% of duration
                $percentPositions = [0.1, 0.3, 0.5];
                foreach ($percentPositions as $percent) {
                    $seekTime = max(1, floor($totalSeconds * $percent));
                    $positions[] = gmdate("H:i:s", $seekTime);
                }
            } else {
                // Fallback fixed positions
                $positions = ['00:00:01', '00:00:05', '00:00:10'];
            }
            
            // Try each position until we get a valid thumbnail
            foreach ($positions as $position) {
                $command = FFMPEG_PATH . " -ss {$position} -i " . escapeshellarg($videoPath) . 
                           " -vframes 1 -q:v " . THUMBNAIL_QUALITY . 
                           " -vf 'scale=" . THUMBNAIL_WIDTH . ":-1' -y " . escapeshellarg($thumbnailPath) . " 2>&1";
                
                @shell_exec($command);
                
                if (file_exists($thumbnailPath)) {
                    $imageInfo = @getimagesize($thumbnailPath);
                    if ($imageInfo !== false) {
                        return $thumbnailPath;
                    }
                    @unlink($thumbnailPath);
                }
            }
        }
        
        // Fallback to a default video thumbnail
        return DEFAULT_VIDEO_THUMBNAIL;
    }
}

if (!function_exists('getThumbnail')) {
    function getThumbnail($url) {
        // Local files
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            if (file_exists($url) && is_readable($url)) {
                if (preg_match('/\.(mp4|mov|avi|wmv|flv|webm|mkv)$/i', $url)) {
                    return generateVideoThumbnail($url);
                }
                // Assume it's an image if it's not a video
                return $url;
            }
            return DEFAULT_THUMBNAIL;
        }

        // YouTube
        $ytId = getYouTubeId($url);
        if ($ytId) {
            $highRes = "https://img.youtube.com/vi/{$ytId}/maxresdefault.jpg";
            $lowRes = "https://img.youtube.com/vi/{$ytId}/hqdefault.jpg";
            
            // Check if high-res exists
            $headers = @get_headers($highRes);
            if ($headers && strpos($headers[0], '200')) {
                return $highRes;
            }
            return $lowRes;
        }

        // Vimeo
        if (stripos($url, 'vimeo.com') !== false) {
            $vimeoId = (int) substr(parse_url($url, PHP_URL_PATH), 1);
            if ($vimeoId) {
                $data = @file_get_contents("https://vimeo.com/api/v2/video/{$vimeoId}.json");
                if ($data) {
                    $data = json_decode($data, true);
                    return $data[0]['thumbnail_large'] ?? 'assets/images/vimeo-thumbnail.jpg';
                }
            }
            return 'assets/images/vimeo-thumbnail.jpg';
        }

        // TikTok
        if (stripos($url, 'tiktok.com') !== false) {
            $videoId = basename(parse_url($url, PHP_URL_PATH));
            if ($videoId) {
                return "https://www.tiktok.com/api/img/?itemId={$videoId}&location=0";
            }
            return 'assets/images/tiktok-thumbnail.jpg';
        }

        // Facebook
        if (stripos($url, 'facebook.com') !== false || stripos($url, 'fb.watch') !== false) {
            // Extract Facebook video ID
            $pattern = '/(?:https?:\/\/)?(?:www\.)?(?:facebook\.com|fb\.watch)\/(?:[^\/]+\/videos\/|video\.php\?v=|watch\/\?v=)(\d+)/';
            if (preg_match($pattern, $url, $matches)) {
                $videoId = $matches[1];
                return "https://graph.facebook.com/{$videoId}/picture";
            }
            return 'assets/images/facebook-thumbnail.jpg';
        }

        // Default fallback
        return DEFAULT_THUMBNAIL;
    }
}
// ------------------------------------------------------------------ //

$officeId = isset($_GET['office_id']) ? intval($_GET['office_id']) : 0;
$galleryItems = [];
$officeName = '';

if ($officeId) {
    $resOffice = $conn->query("SELECT office_name FROM offices WHERE id = $officeId");
    if ($resOffice && $resOffice->num_rows > 0) {
        $officeData = $resOffice->fetch_assoc();
        $officeName = htmlspecialchars($officeData['office_name']);
    }

    $resGallery = $conn->query("SELECT gc.id, gc.media_type, gc.file_path, gc.caption, gc.title, gc.video_link 
                               FROM gallery_content gc 
                               JOIN sections s ON gc.section_id = s.id 
                               WHERE s.office_id = $officeId 
                               ORDER BY gc.created_at DESC");
    if ($resGallery && $resGallery->num_rows > 0) {
        while ($row = $resGallery->fetch_assoc()) {
            $galleryItems[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Gallery - <?php echo $officeName; ?></title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Preconnect to external domains -->
  <link rel="preconnect" href="https://www.youtube.com">
  <link rel="preconnect" href="https://i.ytimg.com">
  <link rel="preconnect" href="https://vimeo.com">
  <link rel="preconnect" href="https://www.tiktok.com">
  <link rel="preconnect" href="https://graph.facebook.com">
  <style>
    :root {
      --video-width: 90vw;
      --video-height: 90vh;
      --primary-color: #3498db;
      --secondary-color: #2c3e50;
      --accent-color: #e74c3c;
      --light-color: #ecf0f1;
      --dark-color: #2c3e50;
      --shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      --transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
      --gallery-item-ratio: 16/9;
    }
    
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background-color: #f8f9fa;
      margin: 0;
      padding: 0;
      color: #333;
      line-height: 1.6;
    }
    
    .gallery-header {
      text-align: center;
      padding: 40px 0;
      color: white;
      margin-bottom: 15px;
      position: relative;
      overflow: hidden;
    }
    
    .gallery-header::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="0 0 100 100"><rect fill="rgba(255,255,255,0.05)" width="50" height="50" x="0" y="0"></rect></svg>');
      opacity: 0.1;
    }
    
    .gallery-header h2 {
      color: black;
      margin: 0 0 15px 0;
      font-size: clamp(1.8rem, 4vw, 2.5rem);
      font-weight: 700;
      position: relative;
      text-shadow: 0 2px 4px rgba(0,0,0,0.2);
      padding-top: 3%;
    }
    
    .gallery-header p {
      color: rgba(0, 0, 0, 0.9);
      margin: 0 auto;
      font-size: clamp(1rem, 2vw, 1.2rem);
      max-width: 700px;
      position: relative;
    }
    
    .gallery-container {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
      gap: 20px;
      max-width: 1500px;
      margin: 0 auto 40px;
      padding: 0 20px;
    }
    
    .gallery-item {
      position: relative;
      width: 100%;
      aspect-ratio: var(--gallery-item-ratio);
      border-radius: 8px;
      overflow: hidden;
      box-shadow: var(--shadow);
      transition: var(--transition);
      background: #222;
      cursor: pointer;
    }
    
    .media-content {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      display: flex;
      justify-content: center;
      align-items: center;
      background: #000;
    }
    
    .video-preview {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
    
    .gallery-item img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }
    
    .gallery-item.portrait {
      --gallery-item-ratio: 9/16;
    }
    
    .gallery-item.landscape {
      --gallery-item-ratio: 16/9;
    }
    
    .gallery-item.square {
      --gallery-item-ratio: 1/1;
    }
    
    .preview-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.5);
      color: white;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      opacity: 0;
      transition: var(--transition);
      z-index: 3;
    }
    
    .gallery-item:hover .preview-overlay {
      opacity: 1;
    }
    
    .play-icon {
      font-size: 3rem;
      margin-bottom: 10px;
      transition: var(--transition);
      color: white;
      text-shadow: 0 2px 4px rgba(0,0,0,0.3);
    }
    
    .gallery-item:hover .play-icon {
      transform: scale(1.2);
      color: var(--accent-color);
    }
    
    .media-info {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      padding: 20px;
      background: linear-gradient(transparent, rgba(0,0,0,0.8));
      color: white;
      transform: translateY(100%);
      transition: var(--transition);
      z-index: 3;
    }
    
    .gallery-item:hover .media-info {
      transform: translateY(0);
    }
    
    .media-title {
      font-size: 1.1rem;
      font-weight: 600;
      margin-bottom: 5px;
    }
    
    .media-caption {
      font-size: 0.9rem;
      opacity: 0.9;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
    }
    
    .media-type-badge {
      position: absolute;
      top: 15px;
      right: 15px;
      background: rgba(0,0,0,0.7);
      color: white;
      padding: 5px 10px;
      border-radius: 20px;
      font-size: 0.7rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 1px;
      z-index: 4;
    }
    
    .fullscreen-modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0,0,0,0.98);
      z-index: 1000;
      justify-content: center;
      align-items: center;
      opacity: 0;
      transition: opacity 0.4s ease;
    }
    
    .fullscreen-modal.active {
      display: flex;
      opacity: 1;
    }
    
    .close-btn {
      position: fixed;
      top: 30px;
      right: 30px;
      font-size: 40px;
      color: white;
      cursor: pointer;
      z-index: 1001;
      transition: var(--transition);
      width: 60px;
      height: 60px;
      border-radius: 50%;
      display: flex;
      justify-content: center;
      align-items: center;
      line-height: 1;
      background: rgba(255,255,255,0.1);
      backdrop-filter: blur(5px);
      border: 1px solid rgba(255,255,255,0.2);
    }
    
    .close-btn:hover {
      color: var(--accent-color);
      transform: rotate(90deg) scale(1.1);
      background: rgba(255,255,255,0.2);
    }
    
    .video-container {
      width: var(--video-width);
      height: var(--video-height);
      max-width: 1200px;
      max-height: 675px;
      position: relative;
    }
    
    .video-container iframe,
    .video-container video {
      width: 100%;
      height: 100%;
      border: none;
      border-radius: 12px;
      box-shadow: 0 0 30px rgba(0,0,0,0.6);
    }
    
    .image-container {
      max-width: 90vw;
      max-height: 90vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    
    .image-container img {
      max-width: 100%;
      max-height: 100%;
      object-fit: contain;
      border-radius: 8px;
      box-shadow: 0 0 30px rgba(0,0,0,0.6);
    }
    
    .no-scroll {
      overflow: hidden;
    }
    
    /* Loading animation */
    @keyframes shimmer {
      0% { background-position: -468px 0 }
      100% { background-position: 468px 0 }
    }
    
    .loading-thumbnail {
      animation: shimmer 1.5s infinite linear;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      color: #666;
      font-size: 0.9rem;
      position: relative;
    }
    
    .loading-thumbnail::after {
      content: 'Loading thumbnail...';
      position: absolute;
    }
    
    /* Error state for thumbnails */
    .thumbnail-error {
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      display: flex;
      justify-content: center;
      align-items: center;
      position: relative;
    }
    
    .thumbnail-error::after {
      content: 'Thumbnail not available';
      position: absolute;
      color: #666;
      font-size: 0.9rem;
    }
    
    /* Filter controls */
    .filter-controls {
      display: flex;
      justify-content: center;
      gap: 15px;
      margin-bottom: 30px;
      flex-wrap: wrap;
    }
    
    .filter-btn {
      padding: 8px 20px;
      background: white;
      border: 1px solid #ddd;
      border-radius: 30px;
      cursor: pointer;
      transition: var(--transition);
      font-weight: 500;
    }
    
    .filter-btn:hover, .filter-btn.active {
      background: var(--primary-color);
      color: white;
      border-color: var(--primary-color);
    }
    
    /* Empty state */
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      grid-column: 1 / -1;
    }
    
    .empty-state i {
      font-size: 3rem;
      color: #ddd;
      margin-bottom: 20px;
    }
    
    .empty-state h3 {
      color: #666;
      margin-bottom: 10px;
    }
    
    .empty-state p {
      color: #999;
      max-width: 500px;
      margin: 0 auto;
    }
    
    @media (max-width: 1200px) {
      .video-container {
        max-width: 1000px;
        max-height: 562px;
      }
    }
    
    @media (max-width: 992px) {
      .gallery-container {
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 20px;
      }
      
      .video-container {
        max-width: 800px;
        max-height: 450px;
      }
      
      .media-info {
        padding: 15px;
      }
    }
    
    @media (max-width: 768px) {
      :root {
        --video-height: 50vh;
      }
      
      .gallery-container {
        grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
        gap: 15px;
        padding: 0 15px;
      }
      
      .close-btn {
        top: 20px;
        right: 20px;
        font-size: 35px;
        width: 50px;
        height: 50px;
      }
      
      .gallery-header {
        padding: 30px 0;
      }
    }
    
    @media (max-width: 576px) {
      :root {
        --video-height: 40vh;
        --video-width: 95vw;
      }
      
      .gallery-container {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
        gap: 12px;
      }
      
      .media-info {
        padding: 12px;
      }
      
      .media-title {
        font-size: 1rem;
      }
      
      .media-caption {
        font-size: 0.8rem;
      }
      
      .play-icon {
        font-size: 2.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="gallery-header">
    <h2><?php echo $officeName; ?> PROGRAMS</h2>
    <p>Explore the various programs and initiatives under <?php echo $officeName; ?></p>
  </div>

  <?php if (!empty($galleryItems)): ?>
    <div class="filter-controls">
      <button class="filter-btn active" data-filter="all">All Media</button>
      <button class="filter-btn" data-filter="image">Images</button>
      <button class="filter-btn" data-filter="video">Videos</button>
      <button class="filter-btn" data-filter="video_link">External Videos</button>
    </div>

    <div class="gallery-container">
      <?php foreach ($galleryItems as $item): ?>
        <?php 
        $thumbnailUrl = DEFAULT_THUMBNAIL;
        $mediaType = $item['media_type'];
        $mediaSrc = '';
        $thumbnailClass = '';
        $orientationClass = 'landscape'; // Default
        
        try {
            if ($mediaType === 'image') {
                $mediaSrc = htmlspecialchars($item['file_path']);
                if (file_exists($item['file_path'])) {
                    // Detect image orientation
                    list($width, $height) = @getimagesize($item['file_path']);
                    if ($width && $height) {
                        $ratio = $width / $height;
                        if ($ratio > 1.2) {
                            $orientationClass = 'landscape';
                        } elseif ($ratio < 0.8) {
                            $orientationClass = 'portrait';
                        } else {
                            $orientationClass = 'square';
                        }
                    }
                    $thumbnailUrl = $mediaSrc;
                    $thumbnailClass = 'loading-thumbnail';
                }
            } elseif ($mediaType === 'video') {
                $mediaSrc = htmlspecialchars($item['file_path']);
                $thumbnailUrl = htmlspecialchars(generateVideoThumbnail($item['file_path']));
                $thumbnailClass = 'video-thumbnail';
                
                // For videos, use the thumbnail to detect orientation
                $thumbInfo = @getimagesize($thumbnailUrl);
                if ($thumbInfo) {
                    $ratio = $thumbInfo[0] / $thumbInfo[1];
                    if ($ratio > 1.2) {
                        $orientationClass = 'landscape';
                    } elseif ($ratio < 0.8) {
                        $orientationClass = 'portrait';
                    } else {
                        $orientationClass = 'square';
                    }
                }
            } elseif ($mediaType === 'video_link') {
                $mediaSrc = htmlspecialchars(getEmbedUrl($item['video_link']));
                $thumbnailUrl = htmlspecialchars(getThumbnail($item['video_link']));
                $thumbnailClass = 'loading-thumbnail';
            }
        } catch (Exception $e) {
            error_log("Thumbnail generation error: " . $e->getMessage());
            $thumbnailClass = 'thumbnail-error';
        }
        ?>
        
        <div class="gallery-item <?php echo $orientationClass; ?>" 
             onclick="openFullscreen('<?php echo $mediaSrc; ?>', '<?php echo $mediaType; ?>')"
             data-media-type="<?php echo $mediaType; ?>"
             <?php if ($mediaType === 'video'): ?>
             data-video-src="<?php echo $mediaSrc; ?>"
             <?php endif; ?>>
          <span class="media-type-badge">
            <?php echo $mediaType === 'image' ? 'Image' : ($mediaType === 'video' ? 'Video' : 'External Video'); ?>
          </span>
          
          <div class="media-content">
            <?php if ($mediaType === 'video'): ?>
            <video class="video-preview" muted loop playsinline preload="metadata" poster="<?php echo $thumbnailUrl; ?>">
              <source src="<?php echo $mediaSrc; ?>" type="video/mp4">
            </video>
            <?php else: ?>
            <img 
              src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='300' height='300' viewBox='0 0 300 300'%3E%3Crect width='300' height='300' fill='%23222'/%3E%3C/svg%3E" 
              data-src="<?php echo $thumbnailUrl; ?>" 
              alt="<?php echo htmlspecialchars($item['caption']); ?>"
              class="<?php echo $thumbnailClass; ?>"
              loading="lazy"
              onerror="this.onerror=null;this.classList.remove('loading-thumbnail', 'video-thumbnail');this.classList.add('thumbnail-error');this.src='<?php echo $mediaType === 'video' ? DEFAULT_VIDEO_THUMBNAIL : DEFAULT_THUMBNAIL; ?>'"
            >
            <?php endif; ?>
          </div>
          
          <div class="preview-overlay">
            <i class="fas <?php echo $mediaType === 'image' ? 'fa-expand' : 'fa-play'; ?> play-icon"></i>
            <span><?php echo $mediaType === 'image' ? 'View Image' : 'Play Video'; ?></span>
          </div>
          <div class="media-info">
            <div class="media-title"><?php echo htmlspecialchars($item['title']); ?></div>
            <div class="media-caption"><?php echo htmlspecialchars($item['caption']); ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="fas fa-images"></i>
      <h3>No Gallery Items Found</h3>
      <p>There are currently no programs or initiatives to display for <?php echo $officeName; ?>.</p>
    </div>
  <?php endif; ?>

  <div class="fullscreen-modal" id="fullscreenModal" tabindex="-1" aria-hidden="true">
    <span class="close-btn" onclick="closeFullscreen(event)" aria-label="Close modal">×</span>
    <div id="fullscreenContent"></div>
  </div>

  <script>
    // Cache DOM elements
    const modal = document.getElementById('fullscreenModal');
    const modalContent = document.getElementById('fullscreenContent');
    const closeBtn = document.querySelector('.close-btn');
    const filterBtns = document.querySelectorAll('.filter-btn');
    const galleryItems = document.querySelectorAll('.gallery-item');
    let currentMediaElement = null;
    
    // Initialize video previews
    document.addEventListener('DOMContentLoaded', function() {
      // Handle video preview on hover
      const videoItems = document.querySelectorAll('.gallery-item[data-media-type="video"]');
      
      videoItems.forEach(item => {
        const video = item.querySelector('.video-preview');
        
        // Ensure video is paused initially and shows first frame
        video.pause();
        
        item.addEventListener('mouseenter', () => {
          if (video) {
            video.currentTime = 0; // Start from beginning
            const playPromise = video.play();
            
            if (playPromise !== undefined) {
              playPromise.catch(error => {
                console.log('Autoplay prevented:', error);
              });
            }
          }
        });
        
        item.addEventListener('mouseleave', () => {
          if (video) {
            video.pause();
            video.currentTime = 0; // Reset to first frame
          }
        });
      });
      
      // Filter gallery items
      filterBtns.forEach(btn => {
        btn.addEventListener('click', () => {
          const filter = btn.dataset.filter;
          
          // Update active button
          filterBtns.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
          
          // Filter items
          galleryItems.forEach(item => {
            if (filter === 'all' || item.dataset.mediaType === filter) {
              item.style.display = 'block';
            } else {
              item.style.display = 'none';
            }
          });
        });
      });
      
      // Enhanced lazy loading with IntersectionObserver
      const lazyImages = [].slice.call(document.querySelectorAll("img[data-src]"));
      
      if ("IntersectionObserver" in window) {
        let lazyImageObserver = new IntersectionObserver(function(entries, observer) {
          entries.forEach(function(entry) {
            if (entry.isIntersecting) {
              let lazyImage = entry.target;
              lazyImage.src = lazyImage.dataset.src;
              lazyImage.classList.remove("loading-thumbnail");
              lazyImageObserver.unobserve(lazyImage);
              
              // Add loaded class for transition effect
              lazyImage.addEventListener('load', () => {
                lazyImage.style.opacity = 1;
              });
              lazyImage.style.opacity = 0;
              lazyImage.style.transition = 'opacity 0.5s ease';
            }
          });
        }, {
          rootMargin: '200px 0px',
          threshold: 0.01
        });

        lazyImages.forEach(function(lazyImage) {
          lazyImageObserver.observe(lazyImage);
        });
      } else {
        // Fallback for browsers without IntersectionObserver
        lazyImages.forEach(function(lazyImage) {
          lazyImage.src = lazyImage.dataset.src;
          lazyImage.classList.remove("loading-thumbnail");
        });
      }
      
      // Preload the first few images immediately
      lazyImages.slice(0, 4).forEach(img => {
        img.src = img.dataset.src;
        img.classList.remove("loading-thumbnail");
      });
    });
    
    function openFullscreen(mediaSrc, mediaType) {
      let content = '';
      
      // Create appropriate content based on media type
      switch(mediaType) {
        case 'video':
          content = `
            <div class="video-container">
              <video controls autoplay playsinline style="width:100%;height:100%">
                <source src="${mediaSrc}" type="video/mp4">
                Your browser does not support HTML5 video.
              </video>
            </div>
          `;
          break;
          
        case 'video_link':
          content = `
            <div class="video-container">
              <iframe src="${mediaSrc}" 
                      frameborder="0" 
                      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" 
                      allowfullscreen
                      style="width:100%;height:100%">
              </iframe>
            </div>
          `;
          break;
          
        case 'image':
          content = `
            <div class="image-container">
              <img src="${mediaSrc}" 
                   alt="Fullscreen Media" 
                   style="max-width:100%; max-height:100%; object-fit:contain;">
            </div>
          `;
          break;
      }
      
      modalContent.innerHTML = content;
      modal.classList.add('active');
      document.body.classList.add('no-scroll');
      modal.focus();
      
      // Store reference to the media element
      if (mediaType === 'video') {
        currentMediaElement = modalContent.querySelector('video');
      } else if (mediaType === 'video_link') {
        currentMediaElement = modalContent.querySelector('iframe');
      } else {
        currentMediaElement = null;
      }
      
      // Add ARIA attributes for accessibility
      modal.setAttribute('aria-hidden', 'false');
      modal.setAttribute('aria-modal', 'true');
      
      // Add keyboard navigation for gallery items in fullscreen
      if (currentMediaElement) {
        currentMediaElement.focus();
      }
    }

    function closeFullscreen(event) {
      if(event) event.stopPropagation();
      
      // Stop all media playback
      if (currentMediaElement) {
        if (currentMediaElement.tagName === 'VIDEO') {
          currentMediaElement.pause();
          currentMediaElement.currentTime = 0;
        } else if (currentMediaElement.tagName === 'IFRAME') {
          // Replace iframe src with blank to stop playback
          currentMediaElement.src = '';
        }
      }
      
      // Fade out modal
      modal.classList.remove('active');
      
      // Wait for transition to complete before removing content
      setTimeout(() => {
        document.body.classList.remove('no-scroll');
        modalContent.innerHTML = '';
        currentMediaElement = null;
        
        // Update ARIA attributes
        modal.setAttribute('aria-hidden', 'true');
        modal.setAttribute('aria-modal', 'false');
      }, 400); // Match this with CSS transition duration
    }

    // Event listeners
    closeBtn.addEventListener('click', closeFullscreen);
    
    modal.addEventListener('click', function(e) {
      if (e.target === this) {
        closeFullscreen();
      }
    });

    document.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && modal.classList.contains('active')) {
        closeFullscreen();
      }
    });
    
    // Handle thumbnail loading errors
    document.addEventListener('error', function(e) {
      if (e.target.tagName === 'IMG' && (e.target.classList.contains('loading-thumbnail') || e.target.classList.contains('video-thumbnail'))) {
        const mediaType = e.target.closest('.gallery-item').dataset.mediaType;
        e.target.src = mediaType === 'video' 
          ? '<?php echo DEFAULT_VIDEO_THUMBNAIL; ?>' 
          : '<?php echo DEFAULT_THUMBNAIL; ?>';
        e.target.classList.remove('loading-thumbnail', 'video-thumbnail');
        e.target.classList.add('thumbnail-error');
      }
    }, true);
  </script>
</body>
</html>