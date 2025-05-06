<!-- sidebar.php -->
<div class="col-md-3 col-lg-2 d-none d-md-block bg-light sidebar">
    <div class="position-sticky">
        <div class="text-center mb-3">
            <?php if (isset($_SESSION['user_photo'])): ?>
                <img src="<?= htmlspecialchars($_SESSION['user_photo'], ENT_QUOTES, 'UTF-8') ?>" alt="User  Photo" class="img-fluid rounded-circle" style="width: 80px; height: 80px;">
            <?php else: ?>
                <img src="ASSETS/user.png" alt="Default Photo" class="img-fluid rounded-circle" style="width: 128px; height: 128px;">
            <?php endif; ?>
            <h5 class="mt-2"><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></h5>
            <p class="text-muted"><?= htmlspecialchars($_SESSION['username'], ENT_QUOTES, 'UTF-8') ?></p>
        </div>
        <h4 class="sidebar-heading">Admin Menu</h4>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link active" href="officemessage.php">Contact Messages</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="other_page.php">Other Page</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="logout.php">Logout</a>
            </li>
        </ul>
    </div>
</div>