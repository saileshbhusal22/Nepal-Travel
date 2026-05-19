document.addEventListener('DOMContentLoaded', function() {
    const storiesGrid = document.getElementById('travelerStoriesGrid');
    const postStoryBtn = document.getElementById('postStoryBtn');
    const storyModal = document.getElementById('storyModal');
    const storyForm = document.getElementById('storyForm');
    let swiperInstance = null;

    function initSwiper() {
        if (typeof Swiper === 'undefined') return;
        
        swiperInstance = new Swiper('.travelerStoriesSwiper', {
            slidesPerView: 1,
            spaceBetween: 30,
            loop: true,
            autoplay: {
                delay: 5000,
                disableOnInteraction: false,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true,
            },
            navigation: {
                nextEl: '.swiper-button-next',
                prevEl: '.swiper-button-prev',
            },
            breakpoints: {
                768: { slidesPerView: 2 },
                1024: { slidesPerView: 3 }
            }
        });
    }

    function init() {
        fetchStories();

        if (postStoryBtn) {
            postStoryBtn.addEventListener('click', () => {
                storyModal.classList.add('active');
            });
        }

        document.querySelectorAll('.closeStoryModal').forEach(btn => {
            btn.addEventListener('click', () => {
                storyModal.classList.remove('active');
            });
        });

        if (storyForm) {
            storyForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const formData = new FormData(storyForm);
                const btn = storyForm.querySelector('button[type="submit"]');
                
                btn.innerText = 'SHARING...';
                btn.disabled = true;

                try {
                    const res = await fetch('api/v1/stories.php', {
                        method: 'POST',
                        body: formData
                    });
                    const data = await res.json();
                    
                    if (data.success) {
                        storyModal.classList.remove('active');
                        storyForm.reset();
                        fetchStories(); // Refresh
                    } else {
                        alert(data.message);
                    }
                } catch (err) {
                    console.error(err);
                } finally {
                    btn.innerText = 'PUBLISH STORY';
                    btn.disabled = false;
                }
            });
        }
    }

    async function fetchStories() {
        if (!storiesGrid) return;
        
        try {
            const res = await fetch('api/v1/stories.php');
            const data = await res.json();
            
            if (data.success) {
                renderStories(data.stories);
                initSwiper();
            }
        } catch (err) {
            console.error('Error fetching stories:', err);
        }
    }

    function renderStories(stories) {
        if (swiperInstance) swiperInstance.destroy(true, true);
        
        storiesGrid.innerHTML = stories.map((s, index) => `
            <div class="swiper-slide">
                <div style="padding: 40px; background: #fcfcfc; border-radius: 24px; border: 1px solid #eee; position: relative; height: 100%; display: flex; flex-direction: column; transition: all 0.3s ease; box-shadow: 0 4px 15px rgba(0,0,0,0.02);">
                    <div style="font-size: 60px; color: #f5a623; opacity: 0.15; position: absolute; top: 20px; left: 20px; line-height: 1; font-family: serif;">“</div>
                    <p style="color: #555; font-style: italic; margin-bottom: 30px; position: relative; z-index: 2; line-height: 1.7; flex-grow: 1; font-size: 15px;">${s.quote}</p>
                    <div style="display: flex; align-items: center; gap: 15px; border-top: 1px solid #f0f0f0; pt: 20px; margin-top: auto; padding-top: 20px;">
                        <div style="width: 54px; height: 54px; border-radius: 50%; background: url('${s.image_path}') center/cover; border: 2px solid #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.1);"></div>
                        <div>
                            <h5 style="margin: 0; font-weight: 800; color: #1b3a5a; font-size: 15px;">${s.name}</h5>
                            <span style="font-size: 12px; color: #999; font-weight: 600;">${s.country || 'Traveler'}</span>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');
    }

    init();
});