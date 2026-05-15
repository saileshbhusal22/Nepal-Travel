document.addEventListener("DOMContentLoaded", () => {
    // API Base Path - Fix for 404 errors on fetch requests
    const BASE_API_PATH = '/Nepal-Travel/Public/api/experience/';
    
    // Debug: Log the API path being used
    console.log('Using API path:', BASE_API_PATH);
    
    // District Data for tagging
    const allDistricts = [
        "KATHMANDU", "LALITPUR", "BHAKTAPUR", "KASKI", "CHITWAN", "SOLUKHUMBU", "MUSTANG", "MANANG", 
        "BARDIYA", "MUGU", "RASUWA", "LUMBINI", "KAPILBASTU", "RUPANDEHI", "ILAM", "TAPLEJUNG", 
        "JHAPA", "MORANG", "SUNSARI", "DHANKUTA", "SINDHUPALCHOK", "DOLAKHA", "GORKHA", "MYAGDI", 
        "BAGLUNG", "SYANGJA", "PALPA", "GULMI", "ARGHAKHANCHI", "PYUTHAN", "ROLPA", "SALYAN", 
        "RUKUM", "JAJARKOT", "SURKHET", "DAILEKH", "JUMLA", "KALIKOT", "DOLPA", "HUMLA", 
        "BAJURA", "ACHHAM", "DOTI", "DADELDHURA", "BAITADI", "DARCHULA", "BAJHANG", 
        "KANCHANPUR", "KAILALI", "BANKE", "DANG", "NAWALPARASI", "PARSA", "BARA", "RAUTAHAT", 
        "SARLAHI", "MAHOTTARI", "DHANUSA", "SIRAHA", "SAPTARI", "UDAYAPUR", "SINDHULI", 
        "RAMECHHAP", "OKHALDHUNGA", "BHOJPUR", "KHOTANG", "PANCHTHAR", "TEHRATHUM", 
        "SANKHUWASABHA", "NUWAKOT", "DHADING", "MAKWANPUR"
    ];

    const districtAliases = {
        "KTM": "KATHMANDU",
        "PKR": "KASKI",
        "POKHARA": "KASKI",
        "PATAN": "LALITPUR",
        "BKT": "BHAKTAPUR",
        "EBC": "SOLUKHUMBU",
        "NAMCHE": "SOLUKHUMBU"
    };

    const feedContainer = document.getElementById("feedContainer");
    const openPostModalBtn = document.getElementById("openPostModalBtn");
    const postModal = document.getElementById("postModal");
    const closePostModal = document.getElementById("closePostModal");
    const createPostForm = document.getElementById("createPostForm");
    const postImage = document.getElementById("postImage");
    const imagePreview = document.getElementById("imagePreview");
    
    // Load Dashboard Stats
    async function loadUserStats() {
        if (!currentUserId || currentUserId == 0) return;
        try {
            const url = BASE_API_PATH + "fetch_user_stats.php";
            console.log('Fetching user stats from:', url);
            const res = await fetch(url);
            if (!res.ok) {
                console.error("Stats API error:", res.status, res.statusText);
                return;
            }
            const text = await res.text();
            console.log('Stats response:', text);
            const data = JSON.parse(text);
            if (data.success) {
                document.getElementById("userPostCount").innerText = data.stats.posts;
                document.getElementById("userLikeCount").innerText = data.stats.likes;
                document.getElementById("userSaveCount").innerText = data.stats.saves;
            }
        } catch (err) { console.error("Stats fail", err); }
    }
    loadUserStats();

    // Load Top Contributors
    async function loadTopContributors() {
        const listEl = document.getElementById("topContributorsList");
        if (!listEl) return;
        try {
            const res = await fetch(BASE_API_PATH + "fetch_top_contributors.php");
            if (!res.ok) {
                console.error("Contributors API error:", res.status, res.statusText);
                return;
            }
            const text = await res.text();
            const data = JSON.parse(text);
            if (data.success && data.contributors.length > 0) {
                listEl.innerHTML = data.contributors.map((c, index) => `
                    <div class="contributor-item" style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px; padding: 10px; border-radius: 12px; background: ${index === 0 ? 'rgba(245, 166, 35, 0.05)' : '#fff'}; border: 1px solid ${index === 0 ? '#f5a62344' : '#f0f0f0'}; transition: all 0.3s ease; cursor: pointer;" onclick="window.location.href='profile.php?id=${c.id}'">
                        <div style="position: relative;">
                            <div class="user-avatar" style="width: 35px; height: 35px; font-size: 14px; background: #1b3a5a;">${c.username.charAt(0).toUpperCase()}</div>
                            ${index === 0 ? '<span style="position: absolute; top: -5px; right: -5px; font-size: 14px;">👑</span>' : ''}
                        </div>
                        <div style="flex: 1;">
                            <div style="font-weight: 800; font-size: 13px; color: #1b3a5a;">${c.username}</div>
                            <div style="font-size: 11px; color: #999;">${c.post_count} Posts • ${c.total_likes} Likes</div>
                        </div>
                        <div style="font-weight: 900; font-size: 12px; color: #f5a623;">${c.score}</div>
                    </div>
                `).join("");
            } else {
                listEl.innerHTML = `<p style="font-size: 12px; color: #999; text-align: center;">Join the community to start contributing!</p>`;
            }
        } catch (err) { console.error("Contributors fail", err); }
    }
    loadTopContributors();

    // Initialize District Lookup for both modals
    setupDistrictLookup('postDestinationInput', 'postDestinationValue', 'districtSuggestions', 'districtFeedback');
    setupDistrictLookup('editPostDestinationInput', 'editPostDestinationValue', 'editDistrictSuggestions', 'editDistrictFeedback');

    function setupDistrictLookup(inputId, hiddenId, listId, feedbackId = null) {
        const input = document.getElementById(inputId);
        const hidden = document.getElementById(hiddenId);
        const list = document.getElementById(listId);
        const feedback = feedbackId ? document.getElementById(feedbackId) : null;
        if(!input) return;

        input.addEventListener('input', function() {
            const val = this.value.toUpperCase();
            list.innerHTML = "";
            hidden.value = val;
            
            if(!val) {
                list.style.display = 'none';
                if(feedback) feedback.innerText = "";
                return;
            }

            let matches = allDistricts.filter(d => d.includes(val));
            for (let alias in districtAliases) {
                if (alias.includes(val)) {
                    const mapped = districtAliases[alias];
                    if (!matches.includes(mapped)) matches.push(mapped);
                }
            }
            
            if(matches.length > 0) {
                list.style.display = 'block';
                if(feedback) {
                    feedback.innerText = `✅ ${matches.length} district(s) matching.`;
                    feedback.style.color = '#3ca341';
                }
                
                matches.forEach(m => {
                    const div = document.createElement('div');
                    div.style.padding = "12px 15px";
                    div.style.cursor = "pointer";
                    div.style.borderBottom = "1px solid #f0f0f0";
                    div.innerText = m;
                    
                    div.addEventListener('mouseenter', () => div.style.background = '#f9f9f9');
                    div.addEventListener('mouseleave', () => div.style.background = 'white');
                    
                    div.addEventListener('click', () => {
                        input.value = m;
                        hidden.value = m;
                        list.style.display = 'none';
                        if(feedback) {
                            feedback.innerText = `✅ Valid district selected: ${m}`;
                            feedback.style.color = "#3ca341";
                        }
                    });
                    list.appendChild(div);
                });
            } else {
                list.style.display = 'none';
                if(feedback) {
                    feedback.innerText = "❌ District not found. Check spelling.";
                    feedback.style.color = "#d32f2f";
                }
            }
        });

        // Close suggesting on outside click
        document.addEventListener('click', (e) => {
            if(e.target !== input) {
                list.style.display = 'none';
            }
        });
    }

    // Modal Logic
    if(openPostModalBtn) {
        openPostModalBtn.addEventListener("click", () => {
            postModal.style.display = "block";
        });
    }

    const openPostModalBtnSide = document.getElementById("openPostModalBtnSide");
    if(openPostModalBtnSide) {
        openPostModalBtnSide.addEventListener("click", () => {
            postModal.style.display = "block";
        });
    }

    if(closePostModal) {
        closePostModal.addEventListener("click", () => {
            postModal.style.display = "none";
        });
    }

    // Image Preview and Cropping
    let cropper = null;

    if(postImage) {
        postImage.addEventListener("change", function() {
            const file = this.files[0];
            if(file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.closest(".image-preview-container").style.display = "block";
                    
                    // Destroy previous cropper instance if exists
                    if (cropper) {
                        cropper.destroy();
                    }
                    
                    // Reset Skip Button UI
                    if(skipCropBtn) {
                        skipCropBtn.innerText = "SKIP CROP / USE FULL PHOTO";
                        skipCropBtn.style.background = "#f0f0f0";
                        skipCropBtn.style.color = "#555";
                    }
                    
                    // Initialize new Cropper
                    cropper = new Cropper(imagePreview, {
                        aspectRatio: 4 / 5, 
                        viewMode: 1, 
                        dragMode: 'crop', // Let user drag the crop box, not the image!
                        zoomOnWheel: false, // Prevent the mouse wheel from zooming so the user can scroll the modal down!
                        autoCropArea: 0.8, // Slightly smaller initial crop box so it's easier to grab
                        restore: false,
                        guides: true,
                        center: true,
                        highlight: false,
                        cropBoxMovable: true,
                        cropBoxResizable: true,
                        toggleDragModeOnDblclick: false,
                    });
                }
                reader.readAsDataURL(file);
            }
        });
    }

    // Skip Crop Handler
    const skipCropBtn = document.getElementById("skipCropBtn");
    if(skipCropBtn) {
        skipCropBtn.addEventListener("click", () => {
            if(cropper) {
                cropper.destroy();
                cropper = null;
                showToast("Cropper disabled. Using full photo.", "info");
                // Update button text to show it's skipped
                skipCropBtn.innerText = "✓ USING FULL PHOTO";
                skipCropBtn.style.background = "#e8f4f8";
                skipCropBtn.style.color = "#1b3a5a";
            }
        });
    }

    // Submit Post
    if(createPostForm) {
        createPostForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const errorDiv = document.getElementById("postError");
            if(errorDiv) errorDiv.style.display = "none";
            
            if (!cropper && (!postImage || postImage.files.length === 0)) {
                if(errorDiv) {
                    errorDiv.innerText = "Please select an image first.";
                    errorDiv.style.display = "block";
                }
                return;
            }
            
            const submitBtn = createPostForm.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerText;
            submitBtn.innerText = "Processing...";
            submitBtn.disabled = true;

            const sendPost = async (formData) => {
                try {
                    const res = await fetch(BASE_API_PATH + "create_post.php", {
                        method: "POST",
                        body: formData
                    });
                    const data = await res.json();
                    if(data.success) {
                        postModal.style.display = "none";
                        createPostForm.reset();
                        imagePreview.closest(".image-preview-container").style.display = "none";
                        
                        if(cropper) {
                            cropper.destroy();
                            cropper = null;
                        }
                        
                        const distFeedback = document.getElementById('districtFeedback');
                        if(distFeedback) distFeedback.innerText = "";
                        
                        loadFeed(); 
                        loadUserStats(); 
                        showToast("Your experience has been shared!", "success");
                    } else {
                        if(errorDiv) {
                            errorDiv.innerText = data.message || "Failed to share post.";
                            errorDiv.style.display = "block";
                        }
                        showToast(data.message || "Post failed.", "error");
                    }
                } catch(err) { 
                    console.error(err); 
                    showToast("Server error. Try again.", "error");
                } finally {
                    submitBtn.innerText = originalBtnText;
                    submitBtn.disabled = false;
                }
            };

            if (cropper) {
                cropper.getCroppedCanvas({
                    width: 1080,
                    height: 1350,
                    imageSmoothingEnabled: true,
                    imageSmoothingQuality: 'high'
                }).toBlob(async (blob) => {
                    const formData = new FormData(createPostForm);
                    formData.set('image', blob, `experience_${Date.now()}.jpg`);
                    await sendPost(formData);
                }, 'image/jpeg', 0.85);
            } else {
                const formData = new FormData(createPostForm);
                await sendPost(formData);
            }
        });
    }

    // Category Filter Logic
    let currentCategory = 'all';
    const catBtns = document.querySelectorAll('.cat-btn');
    catBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            currentCategory = btn.dataset.category;
            catBtns.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
            loadFeed(currentMapFilter, currentMyPostsFilter, feedSearchInput?.value || "", currentSort, currentCategory);
        });
    });

    // Sidebar Filter: My Posts
    let currentMyPostsFilter = false;
    const myPostsBtn = document.getElementById("myPostsFilterBtn");
    
    if(myPostsBtn) {
        myPostsBtn.addEventListener("click", () => {
            currentMyPostsFilter = !currentMyPostsFilter;
            myPostsBtn.classList.toggle("active", currentMyPostsFilter);
            myPostsBtn.innerText = currentMyPostsFilter ? "Showing All Posts" : "My Shared Memories";
            
            // Clear map highlights when toggling "My Posts" to avoid confusion
            if(currentMyPostsFilter) {
                currentMapFilter = null;
                document.querySelectorAll(".map-wrapper .district").forEach(d => d.style.fill = "");
                if(clearFilterBtn) clearFilterBtn.style.display = "none";
                if(cardDistrictTitle) cardDistrictTitle.innerText = "My Memories";
                if(cardDistrictDesc) cardDistrictDesc.innerText = "You are currently viewing only the experiences you have shared. Click 'Showing All Posts' to see the world again.";
            } else {
                if(cardDistrictTitle) cardDistrictTitle.innerText = "Discover Nepal";
                if(cardDistrictDesc) cardDistrictDesc.innerText = "Select a district from the map to instantly filter the feed and reveal regional experiences.";
            }
            
            loadFeed(null, currentMyPostsFilter, "", currentSort, currentCategory);
        });
    }

    // Live Search Logic (Debounced)
    const feedSearchInput = document.getElementById("feedSearchInput");
    let searchTimeout;
    if (feedSearchInput) {
        feedSearchInput.addEventListener("input", (e) => {
            clearTimeout(searchTimeout);
            const val = e.target.value.trim();
            searchTimeout = setTimeout(() => {
                // If searching, clear other filters for clarity, or combine them?
                if (val && currentMapFilter) {
                    currentMapFilter = null;
                    document.querySelectorAll(".map-wrapper .district").forEach(d => d.style.fill = "");
                    if(clearFilterBtn) clearFilterBtn.style.display = "none";
                }
                loadFeed(currentMapFilter, currentMyPostsFilter, val, currentSort, currentCategory);
            }, 400);
        });
    }
    // Feed Sorting Logic
    let currentSort = 'latest';
    const filterTabs = document.querySelectorAll('.filter-tab');
    
    filterTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            currentSort = tab.dataset.sort;
            filterTabs.forEach(t => {
                t.classList.remove('active');
            });
            tab.classList.add('active');
            
            loadFeed(currentMapFilter, currentMyPostsFilter, feedSearchInput?.value || "", currentSort, currentCategory);
        });
    });

    async function loadFeed(destination = null, myPosts = false, search = "", sort = 'latest', category = 'all') {
        const feedContainer = document.getElementById("feedContainer");
        if (!feedContainer) return;
        
        // Show Skeleton Loaders
        feedContainer.innerHTML = Array(3).fill(`
            <div class="skeleton-card">
                <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 15px;">
                    <div class="shimmer skeleton-avatar"></div>
                    <div>
                        <div class="shimmer skeleton-title"></div>
                        <div class="shimmer skeleton-subtitle"></div>
                    </div>
                </div>
                <div class="shimmer skeleton-img"></div>
                <div class="shimmer skeleton-text"></div>
                <div class="shimmer skeleton-text" style="width: 80%;"></div>
            </div>
        `).join("");
        
        try {
            let url = BASE_API_PATH + `fetch_feed.php?page=1`;
            if (destination) url += `&destination=${encodeURIComponent(destination)}`;
            if (myPosts) url += `&my_posts=1`;
            if (search) url += `&search=${encodeURIComponent(search)}`;
            if (sort) url += `&sort=${sort}`;
            if (category && category !== 'all') url += `&category=${encodeURIComponent(category)}`;
            
            const res = await fetch(url);
            if (!res.ok) {
                console.error("Feed API error:", res.status, res.statusText, url);
                feedContainer.innerHTML = `<p style="color: #d32f2f;">Error loading feed (${res.status}). Check console.</p>`;
                return;
            }
            const text = await res.text();
            const data = JSON.parse(text);
            
            if (data.success) {
                if (data.posts.length === 0) {
                    feedContainer.innerHTML = `
                        <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 16px; border: 2px dashed #eee;">
                            <span style="font-size: 50px; display: block; margin-bottom: 20px;">🗺️</span>
                            <h3 style="color: #1b3a5a;">No stories found here yet</h3>
                            <p style="color: #666;">Be the first to share an experience from this region!</p>
                            <button onclick="document.getElementById('openPostModalBtnSide').click()" class="premium-btn" style="margin-top: 20px;">Share Memory</button>
                        </div>
                    `;
                    return;
                }
                
                feedContainer.innerHTML = "";
                data.posts.forEach(post => {
                    const postEl = document.createElement("article");
                    postEl.className = "feed-post";
                    postEl.id = `post-card-${post.id}`;
                    
                    const username = post.username ? post.username : "Anonymous Traveler";
                    const avatarText = username.charAt(0).toUpperCase();
                    
                    const likedClass = post.has_liked ? "liked" : "";
                    const savedClass = post.has_saved ? "saved" : "";
                    
                    const likeIcon = post.has_liked ? '❤️' : '🤍';
                    const saveIcon = post.has_saved ? '🔖' : '📑';
                    
                    const isOwner = currentUserId && String(post.user_id) === String(currentUserId);
                    const optionsBtn = isOwner ? `
                        <div class="post-options-container">
                            <button class="options-trigger" data-id="${post.id}">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                    <circle cx="12" cy="12" r="1"></circle>
                                    <circle cx="12" cy="5" r="1"></circle>
                                    <circle cx="12" cy="19" r="1"></circle>
                                </svg>
                            </button>
                            <div class="options-dropdown" id="options-${post.id}">
                                <button class="edit-option" data-post='${JSON.stringify(post).replace(/'/g, "&apos;")}'>✏️ Edit Post</button>
                                <button class="delete-option" data-id="${post.id}">🗑️ Delete Post</button>
                            </div>
                        </div>
                    ` : "";

                    let locationText = "";
                    if(post.location && post.destination) {
                        locationText = `${post.location}, ${post.destination}`;
                    } else if (post.location) {
                        locationText = post.location;
                    } else if (post.destination) {
                        locationText = post.destination;
                    }
                    
                    const destTag = locationText ? `<span style="font-size: 0.7rem; background: #ecedf1; padding: 3px 12px; border-radius: 20px; margin-left: 12px; color: #555; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">📍 ${escapeHtml(locationText)}</span>` : "";
                    
                    postEl.innerHTML = `
                        <div class="post-header">
                            <div class="user-avatar" style="background: ${isOwner ? 'linear-gradient(135deg, #1b3a5a, #2c537a)' : '#1b3a5a'}">${avatarText}</div>
                            <div class="post-user-info">
                                <h4>${username} ${destTag}</h4>
                                <span class="post-date">${new Date(post.created_at).toLocaleDateString(undefined, {year: 'numeric', month: 'long', day: 'numeric'})}</span>
                            </div>
                            ${optionsBtn}
                        </div>
                        
                        <div class="post-image-wrapper">
                            <img src="${normalizeImagePath(post.image_path)}" alt="Experience" class="post-image" loading="lazy">
                        </div>

                        <div style="padding: 15px 20px 0;">
                            ${post.tags ? `<span class="post-tag-badge">${post.tags}</span>` : ''}
                        </div>
                        
                        <div class="post-actions">
                            <button class="action-btn like-btn ${likedClass}" data-id="${post.id}">
                                <span class="icon" style="transition: transform 0.2s;">${likeIcon}</span>
                                <span class="action-stats like-count">${post.like_count}</span>
                            </button>
                            
                            <button class="action-btn comment-btn" onclick="document.getElementById('comment-input-${post.id}').focus()">
                                💬 <span class="action-stats">${post.comment_count}</span>
                            </button>
                            
                            <button class="action-btn save-btn ${savedClass}" data-id="${post.id}" style="margin-left: auto;">
                                <span class="icon">${saveIcon}</span>
                            </button>
                        </div>
                        
                        <div class="post-caption">
                            <strong>${username}</strong> ${escapeHtml(post.caption)}
                        </div>
                        
                        <div class="post-comments">
                            <div class="comment-list" id="comments-${post.id}"></div>
                            <form class="add-comment-form" data-id="${post.id}">
                                <input type="text" id="comment-input-${post.id}" placeholder="Add a comment..." required>
                                <button type="submit">Post</button>
                            </form>
                        </div>
                    `;
                    feedContainer.appendChild(postEl);
                    loadComments(post.id);
                });
                
                attachActionListeners();
                attachPostOptionsListeners();
            }
        } catch (err) {
            console.error(err);
            feedContainer.innerHTML = "<p>Error loading feed.</p>";
        }
    }

    function attachPostOptionsListeners() {
        // Toggle Dropdown
        document.querySelectorAll(".options-trigger").forEach(btn => {
            btn.addEventListener("click", (e) => {
                e.stopPropagation();
                const postId = btn.dataset.id;
                const dropdown = document.getElementById(`options-${postId}`);
                
                // Close all other dropdowns first
                document.querySelectorAll(".options-dropdown").forEach(d => {
                    if (d.id !== `options-${postId}`) d.style.display = "none";
                });
                
                dropdown.style.display = dropdown.style.display === "block" ? "none" : "block";
            });
        });

        // Close dropdown on outside click
        document.addEventListener("click", () => {
            document.querySelectorAll(".options-dropdown").forEach(d => d.style.display = "none");
        });

        // Delete Post
        document.querySelectorAll(".delete-option").forEach(btn => {
            btn.addEventListener("click", async function() {
                const postId = this.dataset.id;
                if (!confirm("Are you sure you want to permanently delete this beautiful memory?")) return;
                
                try {
                    const res = await fetch(BASE_API_PATH + "delete_post.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/json"},
                        body: JSON.stringify({post_id: postId})
                    });
                    const data = await res.json();
                    if(data.success) {
                        const card = document.getElementById(`post-card-${postId}`);
                        card.style.opacity = "0";
                        card.style.transform = "scale(0.9)";
                        setTimeout(() => card.remove(), 400);
                        showToast("Memorable post deleted.", "success");
                    } else {
                        showToast(data.message || "Failed to delete post.", "error");
                    }
                } catch(err) { console.error(err); }
            });
        });

        // Edit Post (Open Modal)
        const editPostModal = document.getElementById("editPostModal");
        const editForm = document.getElementById("editPostForm");
        
        document.querySelectorAll(".edit-option").forEach(btn => {
            btn.addEventListener("click", function() {
                const post = JSON.parse(this.dataset.post);
                document.getElementById("editPostId").value = post.id;
                document.getElementById("editPostCaption").value = post.caption;
                document.getElementById("editPostLocation").value = post.location || "";
                document.getElementById("editPostDestinationInput").value = post.destination || "";
                document.getElementById("editPostDestinationValue").value = post.destination || "";
                document.getElementById("editImagePreview").src = normalizeImagePath(post.image_path);
                
                editPostModal.style.display = "block";
            });
        });

        if(document.getElementById("closeEditModal")) {
            document.getElementById("closeEditModal").addEventListener("click", () => {
                editPostModal.style.display = "none";
            });
        }
    }

    async function loadComments(postId) {
        const commentList = document.getElementById(`comments-${postId}`);
        if(!commentList) return;
        
        try {
            const res = await fetch(BASE_API_PATH + `fetch_comments.php?post_id=${postId}`);
            if (!res.ok) {
                console.error("Comments API error:", res.status);
                return;
            }
            const text = await res.text();
            const data = JSON.parse(text);
            if(data.success) {
                commentList.innerHTML = "";
                data.comments.forEach(c => {
                    const cName = c.username ? c.username : "Anonymous";
                    commentList.innerHTML += `
                        <div class="comment-item">
                            <strong>${cName}</strong> ${escapeHtml(c.comment_text)}
                        </div>
                    `;
                });
            }
        } catch(err) {
            console.error(err);
        }
    }

    function attachActionListeners() {
        document.querySelectorAll(".like-btn").forEach(btn => {
            btn.addEventListener("click", async function() {
                if (!currentUserId || currentUserId == 0) {
                    showToast("Please log in to like posts.", "info");
                    return;
                }
                const postId = this.dataset.id;
                const icon = this.querySelector(".icon");
                const countEl = this.querySelector(".like-count");
                
                try {
                    const res = await fetch(BASE_API_PATH + "toggle_like.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/json"},
                        body: JSON.stringify({post_id: postId})
                    });
                    const data = await res.json();
                    
                    if(data.success) {
                        let count = parseInt(countEl.innerText);
                        if(data.action === "liked") {
                            this.classList.add("liked");
                            icon.innerText = "❤️";
                            countEl.innerText = count + 1;
                            icon.style.transform = "scale(1.3)";
                            setTimeout(() => icon.style.transform = "scale(1)", 200);
                        } else {
                            this.classList.remove("liked");
                            icon.innerText = "🤍";
                            countEl.innerText = count - 1;
                        }
                    } else {
                        showToast(data.message || "Failed to toggle like.", "error");
                    }
                } catch(err) { console.error(err); }
            });
        });

        document.querySelectorAll(".save-btn").forEach(btn => {
            btn.addEventListener("click", async function() {
                if (!currentUserId || currentUserId == 0) {
                    showToast("Please log in to save posts.", "info");
                    return;
                }
                const postId = this.dataset.id;
                const icon = this.querySelector(".icon");
                try {
                    const res = await fetch(BASE_API_PATH + "toggle_save.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/json"},
                        body: JSON.stringify({post_id: postId})
                    });
                    const data = await res.json();
                    if(data.success) {
                        if(data.action === "saved") {
                            this.classList.add("saved");
                            icon.innerText = "🔖";
                            icon.style.transform = "scale(1.2)";
                            setTimeout(() => icon.style.transform = "scale(1)", 200);
                            showToast("Post saved to your collection.", "success");
                        } else {
                            this.classList.remove("saved");
                            icon.innerText = "📑";
                        }
                    } else {
                        showToast(data.message || "Failed to save post.", "error");
                    }
                } catch(err) { console.error(err); }
            });
        });

        document.querySelectorAll(".add-comment-form").forEach(form => {
            form.addEventListener("submit", async function(e) {
                e.preventDefault();
                if (!currentUserId || currentUserId == 0) {
                    showToast("Please log in to comment.", "info");
                    return;
                }
                const postId = this.dataset.id;
                const input = document.getElementById(`comment-input-${postId}`);
                const text = input.value;
                
                try {
                    const res = await fetch(BASE_API_PATH + "add_comment.php", {
                        method: "POST",
                        headers: {"Content-Type": "application/json"},
                        body: JSON.stringify({post_id: postId, comment: text})
                    });
                    const data = await res.json();
                    if(data.success) {
                        input.value = "";
                        const commentList = document.getElementById(`comments-${postId}`);
                        commentList.innerHTML += `
                            <div class="comment-item">
                                <strong>${data.comment.username}</strong> ${escapeHtml(data.comment.comment_text)}
                            </div>
                        `;
                        showToast("Comment posted!", "success");
                    } else {
                        showToast(data.message || "Failed to post comment.", "error");
                    }
                } catch(err) { console.error(err); }
            });
        });
    }

    // Edit Post Submission
    const editPostForm = document.getElementById("editPostForm");
    if(editPostForm) {
        editPostForm.addEventListener("submit", async (e) => {
            e.preventDefault();
            const btn = editPostForm.querySelector('button[type="submit"]');
            const originalText = btn.innerText;
            btn.innerText = "Saving...";
            btn.disabled = true;

            const formData = new FormData(editPostForm);
            try {
                const res = await fetch(BASE_API_PATH + "update_post.php", {
                    method: "POST",
                    body: formData
                });
                const data = await res.json();
                if(data.success) {
                    document.getElementById("editPostModal").style.display = "none";
                    showToast("Post updated successfully!", "success");
                    loadFeed(); // Refresh to show changes
                } else {
                    const err = document.getElementById("editPostError");
                    err.innerText = data.message || "Update failed.";
                    err.style.display = "block";
                    showToast(data.message || "Update failed.", "error");
                }
            } catch(err) { console.error(err); }
            finally {
                btn.innerText = originalText;
                btn.disabled = false;
            }
        });
    }


    function escapeHtml(unsafe) {
        return (unsafe || "").replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
    }

    // Normalize image paths to ensure they resolve correctly
    function normalizeImagePath(imagePath) {
        if (!imagePath) return '/Nepal-Travel/images/experience/error.png';
        // Remove any leading slashes or base path prefixes
        let cleaned = imagePath.replace(/^\/+/, '').replace(/^Nepal-Travel\/?/i, '');
        // If it's just a filename, add the directory
        if (!cleaned.includes('/')) {
            cleaned = 'images/experience/' + cleaned;
        }
        // Ensure it starts with /Nepal-Travel/
        if (!cleaned.startsWith('/Nepal-Travel/')) {
            cleaned = '/Nepal-Travel/' + cleaned;
        }
        return cleaned;
    }

    // Apply Province Classes (Safe injection)
    const provinceMapping = {
        'koshi': ['TAPLEJUNG', 'PANCHTHAR', 'ILAM', 'JHAPA', 'MORANG', 'SUNSARI', 'DHANKUTA', 'TEHRATHUM', 'BHOJPUR', 'SANKHUWASABHA', 'SOLUKHUMBU', 'KHOTANG', 'OKHALDHUNGA', 'UDAYAPUR'],
        'madhesh': ['SAPTARI', 'SIRAHA', 'DHANUSA', 'MAHOTTARI', 'SARLAHI', 'BARA', 'PARSA', 'RAUTAHAT'],
        'bagmati': ['SINDHULI', 'RAMECHHAP', 'DOLAKHA', 'SINDHUPALCHOK', 'KAVREPALANCHOK', 'LALITPUR', 'BHAKTAPUR', 'KATHMANDU', 'NUWAKOT', 'RASUWA', 'DHADING', 'MAKWANPUR', 'CHITWAN'],
        'gandaki': ['GORKHA', 'LAMJUNG', 'TANAHU', 'SYANGJA', 'KASKI', 'MANANG', 'MUSTANG', 'MYAGDI', 'PARBAT', 'BAGLUNG', 'NAWALPUR'],
        'lumbini': ['PARASI', 'RUPANDEHI', 'KAPILBASTU', 'PALPA', 'ARGHAKHANCHI', 'GULMI', 'RUKUM_EAST', 'ROLPA', 'PYUTHAN', 'DANG', 'BANKE', 'BARDIYA'],
        'karnali': ['RUKUM_WEST', 'SALYAN', 'DOLPA', 'JUMLA', 'MUGU', 'HUMLA', 'KALIKOT', 'JAJARKOT', 'DAILEKH', 'SURKHET'],
        'sudurpashchim': ['BAJURA', 'BAJHANG', 'DOTI', 'ACHHAM', 'DARCHULA', 'BAITADI', 'DADELDHURA', 'KANCHANPUR', 'KAILALI']
    };

    Object.keys(provinceMapping).forEach(prov => {
        provinceMapping[prov].forEach(distId => {
            const el = document.getElementById(distId);
            if (el) el.classList.add(`province-${prov}`);
        });
    });

    // Map Interactions
    let currentMapFilter = null;
    const clearFilterBtn = document.getElementById("clearMapFilterBtn");
    const cardDistrictTitle = document.getElementById("cardDistrictTitle");
    const cardDistrictDesc = document.getElementById("cardDistrictDesc");
    const previewBox = document.getElementById("districtPreviewBox");
    const previewImg = document.getElementById("districtPreviewImg");
    const previewCount = document.getElementById("previewCountText");
    
    document.querySelectorAll(".map-wrapper .district").forEach(district => {
        district.addEventListener("click", async function() {
            const distId = this.getAttribute("id");
            const destName = this.getAttribute("data-name") || distId;
            
            // UI state: toggle logic
            if (currentMapFilter === distId) {
                currentMapFilter = null;
                this.classList.remove('active');
                if(clearFilterBtn) clearFilterBtn.style.display = "none";
                if(previewBox) previewBox.style.display = "none";
                if(cardDistrictTitle) cardDistrictTitle.innerText = "Discover Nepal";
                if(cardDistrictDesc) cardDistrictDesc.innerText = "Select a district from the map to instantly filter the feed.";
                loadFeed(null, currentMyPostsFilter, feedSearchInput?.value || "", currentSort, currentCategory);
                return;
            }

            // Set filter
            currentMapFilter = distId;
            document.querySelectorAll(".map-wrapper .district").forEach(d => d.classList.remove('active'));
            this.classList.add('active');
            
            if(cardDistrictTitle) cardDistrictTitle.innerText = destName;
            if(clearFilterBtn) clearFilterBtn.style.display = "block";
            
            loadFeed(currentMapFilter, currentMyPostsFilter, feedSearchInput?.value || "", currentSort, currentCategory);
            
            // Fetch Preview Data
            try {
                const res = await fetch(BASE_API_PATH + `fetch_district_preview.php?district=${encodeURIComponent(distId)}`);
                if (!res.ok) {
                    console.error("Preview API error:", res.status);
                    return;
                }
                const text = await res.text();
                const data = JSON.parse(text);
                if(data.success && data.count > 0) {
                    if(cardDistrictDesc) cardDistrictDesc.innerText = `Explore ${data.count} authentic experiences shared by travelers in ${destName}.`;
                    if(previewImg) previewImg.src = normalizeImagePath(data.image);
                    if(previewCount) previewCount.innerText = `${data.count} Experiences`;
                    if(previewBox) previewBox.style.display = "block";
                } else {
                    if(cardDistrictDesc) cardDistrictDesc.innerText = `Be the first to share an experience from ${destName}!`;
                    if(previewBox) previewBox.style.display = "none";
                }
            } catch(err) { console.error(err); }
        });

        district.addEventListener("mouseenter", function() {
            const destName = this.getAttribute("data-name") || this.id;
            if(!currentMapFilter && cardDistrictTitle) {
                cardDistrictTitle.innerText = destName;
            }
        });

        district.addEventListener("mouseleave", function() {
            if(!currentMapFilter && cardDistrictTitle) {
                 cardDistrictTitle.innerText = "Discover Nepal";
            }
        });
    });
    
    if (clearFilterBtn) {
        clearFilterBtn.addEventListener("click", () => {
            currentMapFilter = null;
            document.querySelectorAll(".map-wrapper .district").forEach(d => {
                d.classList.remove('active');
            });
            clearFilterBtn.style.display = "none";
            if(previewBox) previewBox.style.display = "none";
            if(cardDistrictTitle) cardDistrictTitle.innerText = "Discover Nepal";
            if(cardDistrictDesc) cardDistrictDesc.innerText = "Select a district from the map to instantly filter the feed.";
            loadFeed(null, currentMyPostsFilter, feedSearchInput?.value || "", currentSort, currentCategory);
        });
    }

    function showToast(message, type = "info") {
        const container = document.getElementById("toastContainer");
        if(!container) return;

        const toast = document.createElement("div");
        toast.className = `toast ${type}`;
        
        const icons = {
            success: "✅",
            error: "❌",
            info: "ℹ️"
        };

        toast.innerHTML = `<span>${icons[type] || ""}</span> ${message}`;
        container.appendChild(toast);

        setTimeout(() => {
            toast.classList.add("fade-out");
            setTimeout(() => toast.remove(), 400);
        }, 3000);
    }

    // Initial load
    loadFeed();
});
