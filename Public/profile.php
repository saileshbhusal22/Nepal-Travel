<?php include '../includes/header.php'; ?>
/**

 * Author: Ramal Gurung
 * Group: L5CG6
 */
<?php
$profile_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($profile_id === 0) {
    header("Location: experience.php");
    exit;
}
?>

<link rel="stylesheet" href="../assets/css/experience.css">

<div class="profile-page-wrapper" style="background: #f8f9fa; min-height: 100vh; padding-top: 120px; padding-bottom: 80px;">
    <div class="container" style="max-width: 900px; margin: 0 auto;">
        <!-- Profile Header Card -->
        <div class="sidebar-card profile-header-card" style="padding: 0; overflow: hidden; margin-bottom: 40px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); background: white;">
            <div style="height: 200px; background: linear-gradient(135deg, #1b3a5a, #2c537a); position: relative;">
                <div style="position: absolute; bottom: -60px; left: 50%; transform: translateX(-50%); border: 5px solid white; border-radius: 50%; background: white;">
                    <div id="profileAvatar" class="avatar-placeholder" style="width: 120px; height: 120px; font-size: 40px; margin: 0; display: flex; align-items: center; justify-content: center; background: #f0f4f8; color: #1b3a5a; border-radius: 50%;">?</div>
                </div>
            </div>
            
            <div style="padding: 80px 40px 40px; text-align: center;">
                <h1 id="profileUsername" style="font-family: 'Playfair Display', serif; font-size: 32px; color: #1b3a5a; margin: 0;">Loading...</h1>
                <p style="color: #666; margin-top: 5px; margin-bottom: 25px;">Passionate Explorer & Community Contributor</p>
                
                <div class="profile-stats-grid" style="display: flex; justify-content: center; gap: 40px; text-align: center;">
                    <div>
                        <span id="profilePostCount" style="display: block; font-weight: 800; font-size: 24px; color: #1b3a5a;">0</span>
                        <span style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 1px;">Experiences</span>
                    </div>
                    <div>
                        <span id="profileLikeCount" style="display: block; font-weight: 800; font-size: 24px; color: #1b3a5a;">0</span>
                        <span style="font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 1px;">Likes Received</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="section-title-wrapper" style="margin-bottom: 30px;">
            <h2 style="font-family: 'Playfair Display', serif; color: #1b3a5a; border-left: 5px solid #f5a623; padding-left: 20px;">Shared Memories</h2>
        </div>

        <div id="profileFeedContainer">
            <!-- Posts loaded here -->
        </div>
    </div>
</div>

<script>
const profileId = <?php echo $profile_id; ?>;
const currentUserId = "<?php echo $_SESSION['user_id'] ?? 0; ?>";

document.addEventListener("DOMContentLoaded", async () => {
    // Load Profile Header
    try {
        const res = await fetch(`api/experience/fetch_profile_header.php?id=${profileId}`);
        const data = await res.json();
        if(data.success) {
            document.getElementById("profileUsername").innerText = data.user.username;
            
            const avatarContainer = document.getElementById("profileAvatar");
            if (data.user.profile_image) {
                avatarContainer.innerHTML = `<img src="../${data.user.profile_image}" onerror="this.onerror=null; this.src='../images/default_avatar.png';" style="width:100%; height:100%; border-radius:50%; object-fit:cover;" alt="Avatar">`;
                avatarContainer.style.background = 'transparent';
                avatarContainer.style.color = 'transparent';
            } else {
                avatarContainer.innerText = data.user.username.charAt(0).toUpperCase();
            }
            
            document.getElementById("profilePostCount").innerText = data.user.post_count;
            document.getElementById("profileLikeCount").innerText = data.user.total_likes;
        }
    } catch(err) { console.error(err); }

    // Load Profile Feed
    loadProfileFeed();
});

async function loadProfileFeed() {
    const container = document.getElementById("profileFeedContainer");
    container.innerHTML = "<p style='text-align:center;'>Loading experiences...</p>";
    
    try {
        const res = await fetch(`api/experience/fetch_feed.php?profile_id=${profileId}`);
        const data = await res.json();
        
        if(data.success) {
            if(data.posts.length === 0) {
                container.innerHTML = "<p style='text-align:center; padding: 40px; color: #666;'>This traveler hasn't shared any experiences yet.</p>";
                return;
            }
            
            container.innerHTML = "";
            data.posts.forEach(post => {
                const postEl = document.createElement("article");
                postEl.className = "feed-post";
                postEl.style.maxWidth = "700px";
                postEl.style.margin = "0 auto 40px";
                
                const likedClass = post.has_liked ? "liked" : "";
                const likeIcon = post.has_liked ? '❤️' : '🤍';
                
                postEl.innerHTML = `
                    <div class="post-header">
                        <div class="user-avatar" style="width:30px; height:30px; font-size:12px; overflow:hidden; display:flex; align-items:center; justify-content:center;">
                            ${post.profile_image ? `<img src="../${post.profile_image}" onerror="this.onerror=null; this.src='../images/default_avatar.png';" style="width:100%; height:100%; object-fit:cover;">` : post.username.charAt(0).toUpperCase()}
                        </div>
                        <div class="post-user-info">
                            <h4 style="font-size:14px;">${post.username} ${post.destination ? `<span style="font-size:10px; margin-left:10px; color:#888;">📍 ${post.destination}</span>` : ''}</h4>
                        </div>
                    </div>
                    <div class="post-image-wrapper">
                        <img src="../${post.image_path.replace(/^Nepal-Travel\//i, '')}" class="post-image" onerror="this.onerror=null; this.src='../images/annapurna_trek.png';">
                    </div>
                    <div class="post-actions">
                        <div class="action-btn likedClass">
                            ❤️ <span class="action-stats">${post.like_count}</span>
                        </div>
                        <div class="action-btn" style="margin-left:20px;">
                            💬 <span class="action-stats">${post.comment_count}</span>
                        </div>
                    </div>
                    <div class="post-caption" style="padding-bottom:20px;">
                        ${post.caption}
                    </div>
                `;
                container.appendChild(postEl);
            });
        }
    } catch(err) { console.error(err); }
}
</script>

<?php include 'includes/footer.php'; ?>