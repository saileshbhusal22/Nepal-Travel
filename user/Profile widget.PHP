<?php
/**
 * Profile Photo Widget
 * Include this inside your dashboard.php wherever you want the profile section.
 * 
 * Requires: session with user_id, user_name, and optionally profile_image
 * Usage:    <?php include 'profile_widget.php'; ?>
 */

// Fetch fresh user data (including profile_image)
require_once '../config/db.php'; // adjust path as needed

$stmt = $pdo->prepare("SELECT name, email, profile_image FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$userData = $stmt->fetch(PDO::FETCH_ASSOC);

$profileImage = (!empty($userData['profile_image']) && file_exists(__DIR__ . '/../' . $userData['profile_image']))
    ? '/' . $userData['profile_image'] . '?t=' . time()
    : null;

$userName  = htmlspecialchars($userData['name']  ?? $_SESSION['user_name'] ?? 'User');
$userEmail = htmlspecialchars($userData['email'] ?? '');
?>

<!-- ========== PROFILE WIDGET STYLES ========== -->
<style>
    .profile-widget {
        font-family: 'Montserrat', sans-serif;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
    }

    /* ---- Avatar ring ---- */
    .avatar-ring {
        position: relative;
        width: 130px;
        height: 130px;
        margin-bottom: 18px;
        cursor: pointer;
    }

    .avatar-ring::before {
        content: '';
        position: absolute;
        inset: -4px;
        border-radius: 50%;
        background: conic-gradient(#f5a623, #285da1, #1b8c6e, #f5a623);
        animation: spin-ring 4s linear infinite;
        z-index: 0;
    }

    @keyframes spin-ring {
        to { transform: rotate(360deg); }
    }

    .avatar-inner {
        position: absolute;
        inset: 4px;
        border-radius: 50%;
        background: #fff;
        overflow: hidden;
        z-index: 1;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .avatar-inner img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 50%;
        display: block;
    }

    .avatar-placeholder {
        width: 100%;
        height: 100%;
        background: linear-gradient(135deg, #1b3a5a 0%, #285da1 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-size: 42px;
        color: #fff;
        font-weight: 700;
        letter-spacing: 1px;
        user-select: none;
    }

    /* Camera overlay on hover */
    .avatar-overlay {
        position: absolute;
        inset: 4px;
        border-radius: 50%;
        background: rgba(0, 0, 0, 0.45);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 4px;
        opacity: 0;
        transition: opacity 0.25s ease;
        z-index: 2;
        cursor: pointer;
    }

    .avatar-ring:hover .avatar-overlay {
        opacity: 1;
    }

    .avatar-overlay svg {
        width: 26px;
        height: 26px;
        fill: #fff;
    }

    .avatar-overlay span {
        font-size: 10px;
        color: #fff;
        font-weight: 600;
        letter-spacing: 0.5px;
        text-transform: uppercase;
    }

    /* Hidden file input */
    #profileFileInput {
        display: none;
    }

    /* User info */
    .profile-name {
        font-size: 20px;
        font-weight: 700;
        color: #1b3a5a;
        margin: 0 0 4px;
        text-align: center;
    }

    .profile-email {
        font-size: 13px;
        color: #7a90a8;
        margin: 0 0 18px;
        text-align: center;
    }

    /* Upload button */
    .btn-upload-photo {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, #1b3a5a, #285da1);
        color: #fff;
        border: none;
        border-radius: 30px;
        padding: 10px 22px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: transform 0.2s, box-shadow 0.2s;
        font-family: 'Montserrat', sans-serif;
        letter-spacing: 0.4px;
    }

    .btn-upload-photo:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 18px rgba(40, 93, 161, 0.35);
    }

    .btn-upload-photo svg {
        width: 15px;
        height: 15px;
        fill: #fff;
    }

    /* Toast notification */
    .profile-toast {
        position: fixed;
        bottom: 30px;
        right: 30px;
        background: #1b3a5a;
        color: #fff;
        padding: 14px 22px;
        border-radius: 10px;
        font-size: 14px;
        font-weight: 600;
        box-shadow: 0 8px 24px rgba(0,0,0,0.2);
        transform: translateY(80px);
        opacity: 0;
        transition: all 0.35s cubic-bezier(0.34,1.56,0.64,1);
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .profile-toast.show {
        transform: translateY(0);
        opacity: 1;
    }

    .profile-toast.success { border-left: 4px solid #1b8c6e; }
    .profile-toast.error   { border-left: 4px solid #e53935; }

    /* Upload progress ring */
    .upload-progress {
        display: none;
        position: absolute;
        inset: 0;
        z-index: 3;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: rgba(255,255,255,0.7);
    }

    .upload-progress.active {
        display: flex;
    }

    .upload-progress svg {
        animation: spin-ring 0.8s linear infinite;
        width: 40px;
        height: 40px;
    }
</style>

<!-- ========== PROFILE WIDGET HTML ========== -->
<div class="profile-widget">

    <!-- Avatar -->
    <div class="avatar-ring" id="avatarRing" onclick="document.getElementById('profileFileInput').click()">
        <div class="avatar-inner" id="avatarInner">
            <?php if ($profileImage): ?>
                <img src="<?php echo $profileImage; ?>" alt="Profile" id="avatarImg">
            <?php else: ?>
                <div class="avatar-placeholder" id="avatarPlaceholder">
                    <?php echo strtoupper(substr($userName, 0, 1)); ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Hover overlay -->
        <div class="avatar-overlay">
            <svg viewBox="0 0 24 24"><path d="M12 15.2a3.2 3.2 0 1 0 0-6.4 3.2 3.2 0 0 0 0 6.4z"/><path d="M9 3L7.17 5H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3.17L15 3H9zm3 14a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/></svg>
            <span>Change</span>
        </div>

        <!-- Spinner -->
        <div class="upload-progress" id="uploadProgress">
            <svg viewBox="0 0 24 24" fill="none" stroke="#285da1" stroke-width="3">
                <circle cx="12" cy="12" r="9" stroke-dasharray="28 56" stroke-linecap="round"/>
            </svg>
        </div>
    </div>

    <!-- Hidden input -->
    <input type="file" id="profileFileInput" accept="image/jpeg,image/png,image/gif,image/webp">

    <!-- Name & Email -->
    <h3 class="profile-name" id="displayName"><?php echo $userName; ?></h3>
    <p class="profile-email"><?php echo $userEmail; ?></p>

    <!-- Upload button -->
    <button class="btn-upload-photo" onclick="document.getElementById('profileFileInput').click()">
        <svg viewBox="0 0 24 24"><path d="M9 3L7.17 5H4a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-3.17L15 3H9zm3 14a5 5 0 1 1 0-10 5 5 0 0 1 0 10z"/></svg>
        Change Photo
    </button>

</div>

<!-- Toast -->
<div class="profile-toast" id="profileToast"></div>

<!-- ========== PROFILE WIDGET JS ========== -->
<script>
(function () {
    const fileInput    = document.getElementById('profileFileInput');
    const avatarInner  = document.getElementById('avatarInner');
    const uploadProgress = document.getElementById('uploadProgress');
    const toast        = document.getElementById('profileToast');

    fileInput.addEventListener('change', function () {
        if (!this.files || !this.files[0]) return;

        const file = this.files[0];
        const maxMB = 5;

        // Client-side size check
        if (file.size > maxMB * 1024 * 1024) {
            showToast('File too large. Max ' + maxMB + 'MB.', 'error');
            return;
        }

        // Preview immediately
        const reader = new FileReader();
        reader.onload = function (e) {
            let img = avatarInner.querySelector('img');
            const placeholder = avatarInner.querySelector('.avatar-placeholder');

            if (!img) {
                img = document.createElement('img');
                img.id = 'avatarImg';
                img.alt = 'Profile';
                avatarInner.innerHTML = '';
                avatarInner.appendChild(img);
            } else if (placeholder) {
                placeholder.remove();
            }
            img.src = e.target.result;
        };
        reader.readAsDataURL(file);

        // Upload
        uploadProgress.classList.add('active');

        const formData = new FormData();
        formData.append('profile_image', file);

        fetch('/Nepal-Travel/user/upload_profile.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(data => {
            uploadProgress.classList.remove('active');
            if (data.success) {
                showToast('✓ Profile photo updated!', 'success');
                // Refresh the image with cache-bust
                const img = avatarInner.querySelector('img');
                if (img) img.src = data.image_url;
                // Update header avatar if present
                const headerAvatar = document.getElementById('headerProfileAvatar');
                if (headerAvatar) headerAvatar.src = data.image_url;
            } else {
                showToast('✗ ' + (data.message || 'Upload failed.'), 'error');
            }
        })
        .catch(() => {
            uploadProgress.classList.remove('active');
            showToast('✗ Upload failed. Please try again.', 'error');
        });

        // Reset so same file can be re-selected
        this.value = '';
    });

    function showToast(msg, type) {
        toast.textContent  = msg;
        toast.className    = 'profile-toast ' + type + ' show';
        setTimeout(() => { toast.classList.remove('show'); }, 3500);
    }
})();
</script>