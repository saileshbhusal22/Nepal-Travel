document.addEventListener('DOMContentLoaded', function () {
    const eventsGrid = document.getElementById('eventsGrid');
    const monthTabs = document.querySelectorAll('.month-tab');
    const categoryCheckboxes = document.querySelectorAll('.category-check');
    const searchInput = document.getElementById('eventSearchInput');
    const applyFiltersBtn = document.getElementById('applyFiltersBtn');

    let currentState = {
        month: 'ALL',
        categories: [],
        query: '',
        view: 'public',
        dateFilter: 'all',
        priceFilter: 'all',
        location: 'all',
        ticketStatus: [],
        featuredOnly: false,
        intlOnly: false,
        limit: 6,
        current_user_id: 0
    };

    let userSubscriptionStatus = false;

    if (typeof isAdmin === 'undefined') { window.isAdmin = false; }

    const PLACEHOLDER_EVENT_IMAGE = 'images/placeholder_event.jpg';

    function resolveEventImageUrl(path, basePrefix = '../') {
        if (!path || path === 'null') return `${basePrefix}${PLACEHOLDER_EVENT_IMAGE}`;
        const trimmed = String(path).trim();
        if (trimmed.startsWith('http://') || trimmed.startsWith('https://') || trimmed.startsWith('data:')) {
            return trimmed;
        }
        if (trimmed.startsWith('images/')) return basePrefix + trimmed;
        if (trimmed.startsWith('/')) return trimmed;
        if (trimmed.startsWith('uploads/')) return `${basePrefix}images/${trimmed}`;
        return `${basePrefix}images/uploads/${trimmed}`;
    }

    function getCategoryFallbackImage(category, eventId = 0) {
        const cat = String(category || '').toUpperCase();
        const pools = {
            FESTIVAL: [
                'images/phewa_sunset.png', 'images/pokhara_lake.png', 'images/family_fun_nepal.png',
                'images/bhaktapur_temple.png', 'images/ktm_durbar.png', 'images/chitwan_rhino.png',
                'images/sarangkot_sunrise.png', 'images/food_drinks_nepal.png',
            ],
            FESTIVALS: [
                'images/phewa_sunset.png', 'images/pokhara_lake.png', 'images/family_fun_nepal.png',
                'images/bhaktapur_temple.png', 'images/ktm_durbar.png', 'images/chitwan_rhino.png',
                'images/sarangkot_sunrise.png', 'images/food_drinks_nepal.png',
            ],
            CONCERT: ['images/kathmandu_night_hero.png', 'images/city_excitement_nepal.png', 'images/phewa_sunset.png'],
            'MUSIC & CONCERT': ['images/kathmandu_night_hero.png', 'images/city_excitement_nepal.png'],
            WORKSHOP: ['images/city_excitement_nepal.png', 'images/ktm_durbar.png', 'images/bhaktapur_temple.png'],
            FOOD: ['images/food_drinks_nepal.png', 'images/family_fun_nepal.png'],
            'FOOD & CUISINE': ['images/food_drinks_nepal.png', 'images/family_fun_nepal.png'],
            SPORTS: ['images/annapurna_trek.png', 'images/everest_trek.png', 'images/sarangkot_sunrise.png'],
            NATURE: ['images/chitwan_rhino.png', 'images/ebc_summit.png', 'images/namche_bazaar.png'],
            ARTS: ['images/bhaktapur_temple.png', 'images/pashupatinath_aarti.png', 'images/ktm_durbar.png'],
            'ARTS & CULTURE': ['images/bhaktapur_temple.png', 'images/pashupatinath_aarti.png', 'images/ktm_durbar.png'],
            RELIGIOUS: ['images/pashupatinath_aarti.png', 'images/lumbini_temple.png'],
            NIGHTLIFE: ['images/kathmandu_night_hero.png', 'images/city_excitement_nepal.png'],
            PHOTOGRAPHY: ['images/sarangkot_sunrise.png', 'images/annapurna_trek.png', 'images/phewa_sunset.png'],
        };
        const pool = pools[cat] || [
            'images/pokhara_lake.png',
            'images/bhaktapur_temple.png',
            'images/chitwan_rhino.png',
            'images/annapurna_trek.png',
            'images/food_drinks_nepal.png',
        ];
        return pool[Math.abs(Number(eventId) || 0) % pool.length];
    }

    function getEventCoverPath(event) {
        if (!event) return PLACEHOLDER_EVENT_IMAGE;
        if (event.display_image) return event.display_image;

        const cover = event.image_path || '';
        if (cover && !cover.includes('placeholder_event')) {
            return cover;
        }
        if (event.gallery_images) {
            const firstGallery = event.gallery_images.split(',')[0].trim();
            if (firstGallery && !firstGallery.includes('placeholder_event')) {
                return firstGallery;
            }
        }
        return getCategoryFallbackImage(event.category, event.id);
    }

    function getEventDisplayImage(event) {
        return getEventCoverPath(event);
    }

    function clearEditQueryParam() {
        const url = new URL(window.location.href);
        if (!url.searchParams.has('edit')) return;
        url.searchParams.delete('edit');
        const next = url.searchParams.toString();
        window.history.replaceState({}, '', url.pathname + (next ? `?${next}` : '') + url.hash);
    }

    window.returnToCalendar = function () {
        clearEditQueryParam();
        const addModal = document.getElementById('addEventModal');
        const successModal = document.getElementById('successModal');
        if (addModal) addModal.classList.remove('active');
        if (successModal) successModal.classList.remove('active');
        document.body.style.overflow = '';
        fetchEvents();
    };

    window.tryOpenEventModal = function () {
        if (!currentUserId) {
            window.location.href = '/Nepal-Travel/user/login.php?redirect=events';
        } else if (!window.hasActiveEventSub && !isAdmin) {
            const subModal = document.getElementById('subscriptionModal');
            if (subModal) {
                subModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            } else {
                window.location.href = 'event-subscription.php';
            }
        } else {
            window.openEventModal();
        }
    };

    function init() {
        const urlParams = new URLSearchParams(window.location.search);
        const m = urlParams.get('month');
        if (m) currentState.month = m.toUpperCase();

        const v = urlParams.get('view');
        if (v === 'private' || v === 'my') {
            currentState.view = v;
            document.querySelectorAll('.view-mode-tab').forEach(t => t.classList.remove('active'));
            const pTab = document.querySelector(`[data-view="${v}"]`);
            if (pTab) pTab.classList.add('active');
        }

        if (urlParams.get('host') === '1') {
            const newUrl = new URL(window.location);
            newUrl.searchParams.delete('host');
            window.history.replaceState({}, '', newUrl.toString());
            if (currentUserId && (window.hasActiveEventSub || isAdmin)) {
                setTimeout(() => {
                    if (typeof window.openEventModal === 'function') {
                        window.openEventModal();
                    }
                }, 200);
            }
        }

        syncFiltersUI();
        fetchEvents();

        // View Mode Toggle
        document.querySelectorAll('.view-mode-tab').forEach(tab => {
            tab.addEventListener('click', () => {
                currentState.view = tab.dataset.view;
                currentState.limit = 6;

                // Update active state
                document.querySelectorAll('.view-mode-tab').forEach(t => {
                    t.classList.remove('active');
                    t.style.background = 'transparent';
                    t.style.color = '#888';
                    t.style.boxShadow = 'none';
                });
                tab.classList.add('active');
                tab.style.background = '#fff';
                tab.style.color = '#111';
                tab.style.boxShadow = '0 4px 10px rgba(0,0,0,0.05)';

                const newUrl = new URL(window.location);
                newUrl.searchParams.set('view', currentState.view);
                window.history.pushState({}, '', newUrl);

                syncFiltersUI();
                fetchEvents();
            });
        });

        // Month tabs
        monthTabs.forEach(tab => {
            tab.addEventListener('click', () => {
                currentState.month = tab.dataset.month;
                currentState.limit = 6;

                const newUrl = new URL(window.location);
                newUrl.searchParams.set('month', currentState.month);
                window.history.pushState({}, '', newUrl);

                syncFiltersUI();
                fetchEvents();
            });
        });

        // Search
        let searchTimeout;
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    currentState.query = e.target.value.trim();
                    currentState.limit = 6;
                    fetchEvents();
                }, 500);
            });
        }

        // Apply Filters
        if (applyFiltersBtn) {
            applyFiltersBtn.addEventListener('click', () => {
                // Categories
                const cats = [];
                document.querySelectorAll('.category-check').forEach(cb => { if (cb.checked) cats.push(cb.value); });
                currentState.categories = cats;

                // Date Filter
                const dateRadio = document.querySelector('input[name="dateFilter"]:checked');
                currentState.dateFilter = dateRadio ? dateRadio.value : 'all';

                // Price Filter
                const priceRadio = document.querySelector('input[name="priceFilter"]:checked');
                currentState.priceFilter = priceRadio ? priceRadio.value : 'all';

                // Location
                const locSelect = document.getElementById('locationFilter');
                currentState.location = locSelect ? locSelect.value : 'all';

                // Ticket Status
                const statuses = [];
                document.querySelectorAll('.ticket-status-check').forEach(cb => { if (cb.checked) statuses.push(cb.value); });
                currentState.ticketStatus = statuses;

                // Event Type
                const featCheck = document.getElementById('featuredOnlyCheck');
                currentState.featuredOnly = featCheck ? featCheck.checked : false;

                const intlCheck = document.getElementById('intlOnlyCheck');
                currentState.intlOnly = intlCheck ? intlCheck.checked : false;

                currentState.limit = 6;
                fetchEvents();
            });
        }

        // View More Button
        const loadMoreBtn = document.getElementById('loadMoreEvents');
        if (loadMoreBtn) {
            loadMoreBtn.addEventListener('click', () => {
                currentState.limit += 6;
                fetchEvents();
            });
            loadMoreBtn.addEventListener('mouseover', () => { loadMoreBtn.style.background = '#111'; loadMoreBtn.style.color = '#fff'; });
            loadMoreBtn.addEventListener('mouseout', () => { loadMoreBtn.style.background = 'white'; loadMoreBtn.style.color = '#111'; });
        }

        // --- 8-SECTION PREMIUM FORM MANAGER ---
        const userEventModal = document.getElementById('addEventModal');
        const userEventForm = document.getElementById('addEventForm');
        const hostEventBtn = document.getElementById('hostEventBtn');
        const adminAddBtn = document.getElementById('openAddEventModal');
        const successModal = document.getElementById('successModal');

        if (!userEventModal || !userEventForm) {
            console.warn("Event management elements missing. Skipping form initialization.");
            return;
        }

        let currentStep = 1;
        const totalSteps = 8;

        function updateProgress() {
            const fill = document.getElementById('formProgressFill');
            const title = document.getElementById('sectionTitle');
            const percentage = (currentStep / totalSteps) * 100;
            fill.style.width = `${percentage}%`;

            const titles = [
                "1. Basic Information", "2. Media Assets", "3. Description",
                "4. Date & Time", "5. Location Details", "6. Ticketing",
                "7. Organizer Info", "8. Premium Promotion"
            ];
            title.innerText = `Section ${currentStep}: ${titles[currentStep - 1]}`;
        }

        function showStep(step) {
            document.querySelectorAll('.form-section').forEach(s => s.classList.remove('active'));
            const targetSection = document.querySelector(`.form-section[data-section="${step}"]`);
            if (targetSection) targetSection.classList.add('active');

            const prevBtn = document.getElementById('prevStepBtn');
            const nextBtn = document.getElementById('nextStepBtn');
            const submitBtn = document.getElementById('submitEventBtn');

            prevBtn.style.visibility = step === 1 ? 'hidden' : 'visible';
            nextBtn.style.display = step === totalSteps ? 'none' : 'block';
            submitBtn.style.display = step === totalSteps ? 'block' : 'none';

            currentStep = step;
            updateProgress();

            // Scroll form to top
            const formContainer = document.getElementById('formScrollContainer');
            if (formContainer) formContainer.scrollTop = 0;
        }

        document.getElementById('nextStepBtn').addEventListener('click', () => {
            if (validateStep(currentStep)) {
                showStep(currentStep + 1);
            }
        });

        document.getElementById('prevStepBtn').addEventListener('click', () => {
            showStep(currentStep - 1);
        });

        function hasEventMediaSelected() {
            const coverInput = document.getElementById('form_image_input');
            if (coverInput && coverInput.files && coverInput.files[0]) {
                return true;
            }
            return Array.from(document.querySelectorAll('.gallery-input')).some(
                (input) => input.files && input.files[0]
            );
        }

        function syncCoverPreviewFromFile(file) {
            if (!file) return;
            const reader = new FileReader();
            reader.onload = (ev) => {
                const previewImg = document.getElementById('preview_img');
                if (previewImg) previewImg.src = ev.target.result;
                if (dropZone) {
                    dropZone.innerHTML = `
                        <img src="${ev.target.result}" style="width: 100%; height: 120px; object-fit: cover; border-radius: 10px; margin-bottom: 10px;">
                        <p style="font-size: 11px; color: #F5A623; font-weight: 800;">IMAGE UPLOADED ✓</p>
                    `;
                    bindDropZone();
                }
            };
            reader.readAsDataURL(file);
        }

        function assignFileToInput(fileInput, file) {
            if (!fileInput || !file) return;
            const transfer = new DataTransfer();
            transfer.items.add(file);
            fileInput.files = transfer.files;
        }

        function buildEventFormData() {
            const fd = new FormData();
            const skipKeys = new Set(['image', 'gallery_images[]']);

            new FormData(userEventForm).forEach((value, key) => {
                if (skipKeys.has(key) || key.startsWith('gallery_images')) {
                    return;
                }
                if (key.startsWith('gallery_existing[')) {
                    return;
                }
                fd.append(key, value);
            });

            const coverInput = document.getElementById('form_image_input');
            if (coverInput && coverInput.files && coverInput.files[0]) {
                fd.append('image', coverInput.files[0], coverInput.files[0].name);
                fd.set('image_path', '');
            }

            document.querySelectorAll('.gallery-slot').forEach((slot) => {
                const index = slot.dataset.index || '0';
                const input = slot.querySelector('.gallery-input');
                const existingInput = slot.querySelector('.gallery-existing-input');
                if (input && input.files && input.files[0]) {
                    fd.append('gallery_images[]', input.files[0], input.files[0].name);
                }
                if (existingInput) {
                    fd.append(`gallery_existing[${index}]`, existingInput.value || '');
                }
            });

            return fd;
        }

        function validateStep(step) {
            const section = document.querySelector(`.form-section[data-section="${step}"]`);
            const requiredFields = section.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    field.style.borderColor = '#e51a2a';
                    isValid = false;
                    field.parentElement.classList.add('shake');
                    setTimeout(() => field.parentElement.classList.remove('shake'), 500);
                } else {
                    field.style.borderColor = '#eee';
                }
            });

            if (step === 2 && !hasEventMediaSelected()) {
                isValid = false;
                if (dropZone) {
                    dropZone.style.borderColor = '#e51a2a';
                    setTimeout(() => { dropZone.style.borderColor = ''; }, 2000);
                }
                alert('Please upload a cover image (top box in Section 2) or at least one gallery photo.');
            }

            if (!isValid) {
                console.warn("Validation failed for step", step);
            }
            return isValid;
        }

        // --- LIVE PREVIEW & DYNAMIC FIELDS ---
        const formInputs = userEventForm.querySelectorAll('input, select, textarea');
        formInputs.forEach(input => {
            input.addEventListener('input', () => updateLivePreview());
            input.addEventListener('change', () => updateLivePreview());
        });

        function updateLivePreview() {
            if (!userEventForm) return;
            const formData = new FormData(userEventForm);

            // Text Fields
            const titleEl = document.getElementById('preview_title_text');
            if (titleEl) titleEl.innerText = formData.get('title') || 'Your Event Title';

            const descEl = document.getElementById('preview_desc_text');
            if (descEl) descEl.innerText = formData.get('description') || 'Add a catchy one-liner in Section 3 to see it appear here in the real-time preview card...';

            // Category & Badge
            const cat = formData.get('category');
            const catBadge = document.getElementById('preview_cat_badge');
            if (catBadge) {
                catBadge.innerText = cat || 'Festival';
                catBadge.style.background = getBadgeColor(cat);
            }

            // Date Bar
            const dateBar = document.getElementById('preview_date_bar');
            const startDate = formData.get('start_date');
            if (dateBar) {
                if (startDate) {
                    const date = new Date(startDate);
                    const options = { day: 'numeric', month: 'short', year: 'numeric' };
                    dateBar.innerText = date.toLocaleDateString('en-GB', options).toUpperCase();

                    // Set hidden month
                    const months = ['JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'];
                    const monthInp = document.getElementById('form_month');
                    if (monthInp) monthInp.value = months[date.getMonth()];
                } else {
                    dateBar.innerText = 'SELECT A DATE';
                }
            }

            // Ticket Box
            const tBox = document.getElementById('preview_ticket_box');
            if (tBox) {
                const isPaid = formData.get('is_paid') === '1';
                const price = formData.get('price_npr');
                const unlimited = formData.get('unlimited_seats') === 'on';
                const seats = formData.get('seats');

                let ticketText = isPaid ? `TICKETS: NPR ${price || '0'}` : 'TICKETS: FREE';
                ticketText += unlimited ? ' | UNLIMITED' : ` | ${seats || '0'} SEATS`;
                tBox.innerText = ticketText;
            }

            // Premium Visuals
            const featCheck = document.getElementById('form_featured_check');
            const previewFeatBadge = document.getElementById('preview_featured_badge');
            const previewCard = document.getElementById('livePreviewCard');
            const premiumHidden = document.getElementById('isPremiumHidden');

            if (featCheck && previewFeatBadge && previewCard) {
                const featured = featCheck.checked;
                previewFeatBadge.style.display = featured ? 'block' : 'none';
                previewCard.style.borderColor = featured ? '#F5A623' : '#f0f0f0';
                previewCard.style.borderWidth = featured ? '3px' : '2px';
                if (premiumHidden) premiumHidden.value = (featured || formData.get('homepage_spotlight')) ? '1' : '0';
            }
        }

        // Character Counters
        const shortDesc = document.getElementById('form_desc');
        const detailedDesc = document.getElementById('form_what_to_expect');

        shortDesc.addEventListener('input', () => {
            document.getElementById('shortDescCount').innerText = `${shortDesc.value.length} / 150`;
        });
        detailedDesc.addEventListener('input', () => {
            document.getElementById('detailedDescCount').innerText = `${detailedDesc.value.length} / 500`;
        });

        // Toggles
        window.togglePricing = (show) => {
            const pricingEl = document.getElementById('pricingInput');
            if (pricingEl) pricingEl.style.display = show ? 'grid' : 'none';
            updateLivePreview();
        };

        userEventForm.querySelectorAll('[name="is_paid"]').forEach((radio) => {
            radio.addEventListener('change', () => {
                window.togglePricing(radio.value === '1');
            });
        });

        const initialPaid = userEventForm.querySelector('[name="is_paid"]:checked');
        window.togglePricing(initialPaid && initialPaid.value === '1');

        const recurringToggle = document.getElementById('recurringToggle');
        recurringToggle.addEventListener('change', () => {
            const show = recurringToggle.checked;
            document.getElementById('recurringOptions').style.display = show ? 'block' : 'none';
            document.getElementById('toggleTrack').style.background = show ? '#F5A623' : '#eee';
            document.getElementById('toggleKnob').style.left = show ? '27px' : '3px';
        });

        const raffleToggle = document.getElementById('raffleToggle');
        raffleToggle.addEventListener('change', () => {
            document.getElementById('raffleFields').style.display = raffleToggle.checked ? 'block' : 'none';
        });

        // Image Handling (single click handler — avoid stacked listeners on reopen)
        const imageInput = document.getElementById('form_image_input');
        const dropZone = document.getElementById('dropZone');
        const dropZoneDefaultHtml = `
            <div style="font-size: 40px; margin-bottom: 15px;">🖼️</div>
            <p style="font-size: 14px; font-weight: 700; color: #111;">Drag & Drop or <span style="color: #F5A623;">Browse</span></p>
            <p style="font-size: 11px; color: #999; margin-top: 5px;">Max 5MB. Supports JPG, PNG, GIF.</p>
        `;

        function bindDropZone() {
            if (!dropZone || !imageInput) return;
            dropZone.onclick = (e) => {
                e.preventDefault();
                e.stopPropagation();
                imageInput.click();
            };
        }

        function resetDropZone() {
            if (!dropZone) return;
            dropZone.innerHTML = dropZoneDefaultHtml;
            if (imageInput) imageInput.value = '';
            bindDropZone();
        }

        if (imageInput) {
            imageInput.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    const imagePathField = userEventForm.querySelector('[name="image_path"]');
                    if (imagePathField) imagePathField.value = '';
                    syncCoverPreviewFromFile(e.target.files[0]);
                }
            });
        }

        if (dropZone && imageInput) {
            dropZone.addEventListener('dragover', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '#F5A623';
            });
            dropZone.addEventListener('dragleave', () => {
                dropZone.style.borderColor = '';
            });
            dropZone.addEventListener('drop', (e) => {
                e.preventDefault();
                dropZone.style.borderColor = '';
                const file = e.dataTransfer.files && e.dataTransfer.files[0];
                if (!file || !file.type.startsWith('image/')) {
                    alert('Please drop an image file (JPG, PNG, GIF, or WebP).');
                    return;
                }
                assignFileToInput(imageInput, file);
                const imagePathField = userEventForm.querySelector('[name="image_path"]');
                if (imagePathField) imagePathField.value = '';
                syncCoverPreviewFromFile(file);
            });
        }

        bindDropZone();

       

        // Gallery Multi-Image Handling
        document.querySelectorAll('.gallery-slot').forEach(slot => {
            const input = slot.querySelector('.gallery-input');
            const preview = slot.querySelector('.gallery-preview');
            const plus = slot.querySelector('.plus-icon');
            const removeBtn = slot.querySelector('.remove-gallery-img');

            slot.addEventListener('click', (e) => {
                if (e.target.classList.contains('remove-gallery-img')) return;
                input.click();
            });

            input.addEventListener('change', (e) => {
                if (e.target.files && e.target.files[0]) {
                    const file = e.target.files[0];
                    const reader = new FileReader();
                    reader.onload = (ev) => {
                        preview.src = ev.target.result;
                        preview.style.display = 'block';
                        plus.style.display = 'none';
                        removeBtn.style.display = 'block';
                        slot.style.borderStyle = 'solid';
                        slot.style.borderColor = '#F5A623';

                        // If no cover yet, use first gallery image as cover preview too
                        const coverInput = document.getElementById('form_image_input');
                        if (coverInput && (!coverInput.files || !coverInput.files[0]) && slot.dataset.index === '0') {
                            assignFileToInput(coverInput, file);
                            const imagePathField = userEventForm.querySelector('[name="image_path"]');
                            if (imagePathField) imagePathField.value = '';
                            syncCoverPreviewFromFile(file);
                        }
                    };
                    reader.readAsDataURL(file);
                }
            });

            removeBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                input.value = '';
                preview.src = '';
                preview.style.display = 'none';
                plus.style.display = 'block';
                removeBtn.style.display = 'none';
                slot.style.borderStyle = 'dashed';
                slot.style.borderColor = '#eee';
                const existingInput = slot.querySelector('.gallery-existing-input');
                if (existingInput) existingInput.value = '';
            });
        });

        function resetGallerySlots() {
            document.querySelectorAll('.gallery-slot').forEach((slot) => {
                const preview = slot.querySelector('.gallery-preview');
                const plus = slot.querySelector('.plus-icon');
                const removeBtn = slot.querySelector('.remove-gallery-img');
                const input = slot.querySelector('.gallery-input');
                const existingInput = slot.querySelector('.gallery-existing-input');
                if (input) input.value = '';
                if (existingInput) existingInput.value = '';
                if (preview) {
                    preview.src = '';
                    preview.style.display = 'none';
                }
                if (plus) plus.style.display = 'block';
                if (removeBtn) removeBtn.style.display = 'none';
                slot.style.borderStyle = 'dashed';
                slot.style.borderColor = '#eee';
            });
        }

        window.openEventModal = () => {
    if (userEventForm) userEventForm.reset();
    currentStep = 1;
    showStep(1);
    updateLivePreview();

    const modalTitle = userEventModal.querySelector('h2');
    if (modalTitle) modalTitle.textContent = 'Host Your Event';
    const submitBtn = document.getElementById('submitEventBtn');
    if (submitBtn) submitBtn.textContent = 'PUBLISH EVENT';
    userEventForm.querySelector('[name="id"]').value = '';
    
    // Reset preview image to placeholder
    const previewImg = document.getElementById('preview_img');
    if (previewImg) previewImg.src = '../images/placeholder_event.jpg';
    
    if (userEventModal) {
        userEventModal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    resetDropZone();

    const freeRadio = userEventForm.querySelector('[name="is_paid"][value="0"]');
    if (freeRadio) freeRadio.checked = true;
    window.togglePricing(false);

            resetGallerySlots();
        };

        if (hostEventBtn) {
            hostEventBtn.onclick = window.tryOpenEventModal;
        }
        if (adminAddBtn) adminAddBtn.onclick = window.openEventModal;
        const closeModalBtn = document.getElementById('closeEventModal');
        if (closeModalBtn) closeModalBtn.onclick = () => {
            clearEditQueryParam();
            userEventModal.classList.remove('active');
            document.body.style.overflow = '';
        };

        const returnToCalendarBtn = document.getElementById('returnToCalendarBtn');
        if (returnToCalendarBtn) {
            returnToCalendarBtn.addEventListener('click', window.returnToCalendar);
        }

        const closeSubscriptionModal = () => {
            const subModalEl = document.getElementById('subscriptionModal');
            if (subModalEl) {
                subModalEl.classList.remove('active');
                document.body.style.overflow = '';
            }
        };

        const simSubBtn = document.getElementById('simulateSubscribeBtn');
        if (simSubBtn) {
            simSubBtn.addEventListener('click', () => {
                window.location.href = 'event-subscription.php';
            });
        }

        const closeSubModalBtn = document.getElementById('closeSubscriptionModal');
        if (closeSubModalBtn) {
            closeSubModalBtn.addEventListener('click', closeSubscriptionModal);
        }

        const subModalEl = document.getElementById('subscriptionModal');
        if (subModalEl) {
            subModalEl.addEventListener('click', (e) => {
                if (e.target === subModalEl) {
                    closeSubscriptionModal();
                }
            });
        }

        // Submit Handler
        userEventForm.onsubmit = async (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('submitEventBtn');
            const originalText = submitBtn.innerText;
            submitBtn.disabled = true;
            submitBtn.innerText = 'PROCESSING...';

            try {
                if (!hasEventMediaSelected()) {
                    alert('Please upload a cover image (Section 2 — top box) or at least one gallery photo before publishing.');
                    showStep(2);
                    return;
                }

                const formData = buildEventFormData();
                const res = await fetch('../api/v1/events.php', { method: 'POST', body: formData });
                const data = await res.json();

                if (data.success) {
                    clearEditQueryParam();
                    userEventModal.classList.remove('active');
                    document.body.style.overflow = '';
                    successModal.classList.add('active');
                } else {
                    alert("Submission error: " + data.message);
                }
            } catch (err) {
                console.error(err);
                alert("Network error. Please check your connection.");
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerText = originalText;
            }
        };

        window.editEvent = async function (id) {
            try {
                const res = await fetch(`../api/v1/events.php?id=${id}`);
                const data = await res.json();
                if (!data.success || !data.event) {
                    alert(data.message || 'Could not load event for editing.');
                    return;
                }

                const e = data.event;
                const form = userEventForm;
                const modalTitle = userEventModal.querySelector('h2');
                if (modalTitle) modalTitle.textContent = 'Edit Your Event';

                form.querySelector('[name="title"]').value = e.title || '';
                form.querySelector('[name="category"]').value = e.category || 'FESTIVAL';
                form.querySelector('[name="tags"]').value = e.tags || '';
                form.querySelector('[name="language"]').value = e.language || 'Both';

                const ageVal = e.age_group || 'All Ages';
                form.querySelectorAll('[name="age_group"]').forEach((radio) => {
                    radio.checked = radio.value === ageVal;
                });

                form.querySelector('[name="description"]').value = e.description || '';
                form.querySelector('[name="what_to_expect"]').value = e.what_to_expect || '';
                form.querySelector('[name="start_date"]').value = e.start_date || '';
                form.querySelector('[name="end_date"]').value = e.end_date || '';
                form.querySelector('[name="start_time"]').value = (e.start_time || '').slice(0, 5);
                form.querySelector('[name="end_time"]').value = (e.end_time || '').slice(0, 5);

                const recurringCheck = document.getElementById('recurringToggle');
                if (recurringCheck) {
                    recurringCheck.checked = e.is_recurring == 1;
                    recurringCheck.dispatchEvent(new Event('change'));
                }
                const freqField = form.querySelector('[name="recurring_frequency"]');
                if (freqField) freqField.value = e.recurring_frequency || 'Weekly';

                form.querySelector('[name="venue_name"]').value = e.venue_name || '';
                form.querySelector('[name="full_address"]').value = e.full_address || '';
                form.querySelector('[name="region"]').value = e.region || e.location || 'Kathmandu Valley';
                form.querySelector('[name="google_maps_link"]').value = e.google_maps_link || '';

                const isPaid = e.is_paid == 1;
                const paidRadio = form.querySelector(`[name="is_paid"][value="${isPaid ? '1' : '0'}"]`);
                if (paidRadio) paidRadio.checked = true;
                if (typeof window.togglePricing === 'function') window.togglePricing(isPaid);
                form.querySelector('[name="price_npr"]').value = e.price_npr || '0';
                form.querySelector('[name="seats"]').value = e.seats || '0';
                const unlimitedSeats = form.querySelector('[name="unlimited_seats"]');
                if (unlimitedSeats) unlimitedSeats.checked = e.unlimited_seats == 1;
                form.querySelector('[name="registration_url"]').value = e.registration_url || e.ticket_link || '';
                form.querySelector('[name="selling_fast_threshold"]').value = e.selling_fast_threshold || '80';

                form.querySelector('[name="organizer_name"]').value = e.organizer_name || '';
                form.querySelector('[name="organizer_contact"]').value = e.organizer_contact || '';
                form.querySelector('[name="organizer_email"]').value = e.organizer_email || '';
                form.querySelector('[name="organizer_website"]').value = e.organizer_website || '';
                form.querySelector('[name="organizer_facebook"]').value = e.organizer_facebook || '';
                form.querySelector('[name="organizer_instagram"]').value = e.organizer_instagram || '';

                const featuredCheck = form.querySelector('[name="is_featured"]');
                if (featuredCheck) featuredCheck.checked = e.is_featured == 1;
                const spotlightCheck = form.querySelector('[name="homepage_spotlight"]');
                if (spotlightCheck) spotlightCheck.checked = e.homepage_spotlight == 1;

                const raffleCheck = document.getElementById('raffleToggle');
                if (raffleCheck) {
                    raffleCheck.checked = e.raffle_enabled == 1;
                    raffleCheck.dispatchEvent(new Event('change'));
                }
                form.querySelector('[name="raffle_draw_time"]').value = e.raffle_draw_time ? e.raffle_draw_time.replace(' ', 'T') : '';
                form.querySelector('[name="raffle_entry_fee"]').value = e.raffle_entry_fee || '0';
                form.querySelector('[name="raffle_prize_1"]').value = e.raffle_prize_1 || '';
                form.querySelector('[name="raffle_prize_2"]').value = e.raffle_prize_2 || '';
                const parkingCheck = form.querySelector('[name="free_parking"]');
                if (parkingCheck) parkingCheck.checked = e.free_parking == 1;

                form.querySelector('[name="id"]').value = e.id;
                form.querySelector('[name="image_path"]').value = e.image_path || '';
                form.querySelector('[name="gallery_images"]').value = e.gallery_images || '';

                resetGallerySlots();

                const coverPath = e.image_path || '';
                const formattedImgPath = coverPath && !coverPath.includes('placeholder_event')
                    ? resolveEventImageUrl(coverPath)
                    : (e.gallery_images ? resolveEventImageUrl(e.gallery_images.split(',')[0].trim()) : '');
                const previewImg = document.getElementById('preview_img');
                if (previewImg) previewImg.src = formattedImgPath;
                const dz = document.getElementById('dropZone');
                if (dz) {
                    if (formattedImgPath) {
                        dz.innerHTML = `<img src="${formattedImgPath}" style="width: 100%; height: 120px; object-fit: cover; border-radius: 10px; margin-bottom: 10px;"><p style="font-size: 11px; color: #F5A623; font-weight: 800;">CURRENT IMAGE</p>`;
                    } else {
                        resetDropZone();
                    }
                    bindDropZone();
                }
                form.querySelector('[name="image_path"]').value = e.image_path || '';

                if (e.gallery_images) {
                    const existingGallery = e.gallery_images.split(',').map((img) => img.trim()).filter(Boolean);
                    document.querySelectorAll('.gallery-slot').forEach((slot, idx) => {
                        const preview = slot.querySelector('.gallery-preview');
                        const plus = slot.querySelector('.plus-icon');
                        const removeBtn = slot.querySelector('.remove-gallery-img');
                        const existingInput = slot.querySelector('.gallery-existing-input');
                        if (existingGallery[idx] && preview && plus && removeBtn) {
                            const galleryPath = existingGallery[idx];
                            if (existingInput) existingInput.value = galleryPath;
                            preview.src = resolveEventImageUrl(galleryPath);
                            preview.style.display = 'block';
                            plus.style.display = 'none';
                            removeBtn.style.display = 'block';
                            slot.style.borderStyle = 'solid';
                            slot.style.borderColor = '#F5A623';
                        }
                    });
                }

                const submitBtn = document.getElementById('submitEventBtn');
                if (submitBtn) submitBtn.textContent = 'SAVE CHANGES';

                userEventModal.classList.add('active');
                document.body.style.overflow = 'hidden';
                showStep(1);
                updateLivePreview();
            } catch (err) {
                console.error(err);
                alert('Failed to open event editor. Please try again.');
            }
        };

        const editId = urlParams.get('edit');
        if (editId) {
            setTimeout(() => window.editEvent(parseInt(editId, 10)), 300);
        }
    }

    function syncFiltersUI() {
        document.querySelectorAll('.view-mode-tab').forEach(t => {
            const isActive = t.dataset.view === currentState.view;
            t.style.background = isActive ? '#fff' : 'transparent';
            t.style.boxShadow = isActive ? '0 4px 10px rgba(0,0,0,0.05)' : 'none';
            t.style.color = isActive ? '#111' : '#888';
            if (isActive) t.classList.add('active'); else t.classList.remove('active');
        });

        monthTabs.forEach(t => {
            const isActive = t.dataset.month === currentState.month;
            t.style.borderBottom = isActive ? '4px solid #f5a623' : '4px solid transparent';
            t.style.color = isActive ? '#111' : '#888';
            t.style.fontWeight = isActive ? '800' : '600';
        });

        categoryCheckboxes.forEach(cb => {
            cb.checked = currentState.categories.includes(cb.value);
        });
    }

    async function fetchEvents() {
        renderSkeletons();
        try {
            const params = new URLSearchParams();
            if (currentState.month) params.append('month', currentState.month);
            if (currentState.categories.length > 0) params.append('category', currentState.categories.join(','));
            if (currentState.query) params.append('q', currentState.query);
            if (currentState.view) params.append('view', currentState.view);

            // New Filters
            if (currentState.dateFilter !== 'all') params.append('date', currentState.dateFilter);
            if (currentState.priceFilter !== 'all') params.append('price', currentState.priceFilter);
            if (currentState.location !== 'all') params.append('location', currentState.location);
            if (currentState.ticketStatus.length > 0) params.append('status', currentState.ticketStatus.join(','));
            if (currentState.featuredOnly) params.append('featured', '1');
            if (currentState.intlOnly) params.append('intl', '1');

            const res = await fetch(`../api/v1/events.php?${params.toString()}`);
            const data = await res.json();

            if (data.success) {
                currentState.current_user_id = data.current_user_id;
                if (typeof data.has_active_event_sub !== 'undefined') {
                    window.hasActiveEventSub = data.has_active_event_sub;
                }
                renderEvents(data.events);
            }
        } catch (err) { console.error(err); }
    }

    function renderSkeletons() {
        const skeletonHtml = Array(6).fill(0).map(() => `
            <div class="skeleton-card"><div class="skeleton-image skeleton-shimmer"></div><div class="skeleton-title skeleton-shimmer"></div></div>
        `).join('');
        if (eventsGrid) eventsGrid.innerHTML = skeletonHtml;
    }

    function renderEvents(events) {
        if (events.length === 0 && currentState.view !== 'my') {
            eventsGrid.innerHTML = '<div style="grid-column:1/-1; text-align:center; padding:100px; color:#888; background:#f9f9f9; border-radius:20px; border:2px dashed #eee;">No events found for this selection.</div>';
            const lb = document.getElementById('loadMoreEvents');
            if (lb) lb.parentElement.style.display = 'none';
            return;
        }

        const totalCount = events.length;
        const sliced = events.slice(0, currentState.limit);

        // Sidebar Spotlight
        const premiumEvents = events.filter(e => e.is_premium == 1);
        renderFeaturedSidebar(premiumEvents);

        let html = '';
        // If in "My Events" view, show an "Add New" card first
        if (currentState.view === 'my') {
            html += `
                <div class="event-card host-event-btn" onclick="window.tryOpenEventModal()" style="height: 100%; min-height: 400px; display: flex; flex-direction: column; justify-content: center; align-items: center; border: 2px dashed #f5a623; border-radius: 20px; background: #fffcf5; cursor: pointer; transition: all 0.3s; transform-origin: center;">
                    <div style="width: 80px; height: 80px; background: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; box-shadow: 0 10px 20px rgba(245, 166, 35, 0.1);">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#f5a623" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    </div>
                    <h3 style="font-family: 'Playfair Display', serif; font-size: 22px; color: #1b3a5a; margin-bottom: 10px;">Post New Event</h3>
                    <p style="font-size: 13px; color: #666; text-align: center; max-width: 200px;">Click here to start listing your next festival or gathering.</p>
                </div>
            `;
        }

        html += renderGridHtml(sliced);
        eventsGrid.innerHTML = html;

        const loadMoreBtn = document.getElementById('loadMoreEvents');
        if (loadMoreBtn) {
            if (sliced.length < totalCount) {
                loadMoreBtn.parentElement.style.display = 'block';
                loadMoreBtn.innerText = `VIEW MORE EVENTS (${totalCount - sliced.length} REMAINING)`;
            } else {
                loadMoreBtn.parentElement.style.display = 'none';
            }
        }
    }

    function renderGridHtml(sortedEvents) {
        const monthOrder = { 'ALL': 0, 'JAN': 1, 'FEB': 2, 'MAR': 3, 'APR': 4, 'MAY': 5, 'JUN': 6, 'JUL': 7, 'AUG': 8, 'SEP': 9, 'OCT': 10, 'NOV': 11, 'DEC': 12 };
        const items = [...sortedEvents].sort((a, b) => {
            if ((b.is_premium || 0) !== (a.is_premium || 0)) return (b.is_premium || 0) - (a.is_premium || 0);
            return (monthOrder[a.month] || 99) - (monthOrder[b.month] || 99);
        });

        return items.map((e, index) => {
            const isPremium = e.is_premium == 1;
            const rawDate = (e.event_date && e.event_date !== 'null') ? e.event_date : '';
            const displayDate = rawDate || (e.month !== 'ALL' ? e.month : 'MAY');
            const suffixMonth = (rawDate && !rawDate.toUpperCase().includes(e.month.toUpperCase())) ? ` ${e.month}` : '';

            return `
            <div class="event-card" style="border: ${isPremium ? '2px solid #f5a623' : '1px solid #f0f0f0'}; border-radius: 16px; overflow: hidden; background: white; display: flex; flex-direction: column; position: relative; transition: transform 0.3s ease;" onmouseover="this.style.transform='translateY(-5px)'" onmouseout="this.style.transform='translateY(0)'">
                ${isPremium ? `<div style="position: absolute; top: 15px; right: 15px; background: #f5a623; color: white; padding: 5px 12px; font-size: 10px; font-weight: 800; border-radius: 4px; z-index: 10;">✨ ${e.featured_badge_text || 'FEATURED'}</div>` : ''}
                ${e.early_bird_text ? `<div style="position: absolute; top: 15px; left: 15px; background: #cc3333; color: white; padding: 5px 12px; font-size: 10px; font-weight: 800; border-radius: 4px; z-index: 10;">${e.early_bird_text}</div>` : ''}
                <a href="event-detail.php?id=${e.id}" style="display: block; position: relative; height: 180px;">
                    <img src="${resolveEventImageUrl(getEventDisplayImage(e))}" onerror="this.onerror=null; this.src='${resolveEventImageUrl(getCategoryFallbackImage(e.category, e.id))}'" style="width: 100%; height: 100%; object-fit: cover;">
                    <div style="position: absolute; bottom: 15px; left: 15px; background: ${e.source === 'hamro_patro' ? '#285da1' : getBadgeColor(e.category)}; color: white; padding: 6px 14px; font-size: 10px; font-weight: 1000; border-radius: 30px; text-transform: uppercase;">
                        ${e.category}
                    </div>
                </a>
                <div style="padding: 24px; flex-grow: 1; display: flex; flex-direction: column;">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 10px;">
                        <h3 style="font-size: 18px; margin: 0; color: #111; font-family: 'Playfair Display', serif;">${e.title}</h3>
                        <div style="display: flex; gap: 5px;">
                            ${e.source === 'local' && (window.isAdmin || e.user_id == currentState.current_user_id) ? `
                                <button onclick="editEvent(${e.id})" style="background: none; border: none; color: #285da1; cursor: pointer; font-size: 14px;">✎</button>
                                <button onclick="deleteEvent(${e.id})" style="background: none; border: none; color: #cc3333; cursor: pointer; font-size: 18px;">×</button>
                            ` : ''}
                        </div>
                    </div>
                    <p style="color: #666; font-size: 14px; line-height: 1.6; margin-bottom: 20px;">${e.description}</p>
                    ${isPremium ? `<div style="background: #fdf8ef; padding: 10px; border-radius: 8px; margin-bottom: 15px; font-size: 11px; font-weight: 700; color: #b45309; text-align: center;">TICKETS: ${e.ticket_price || 'FREE'} | ${e.seats || 'UNLIMITED'} SEATS</div>` : ''}
                    <a href="event-detail.php?id=${e.id}" class="premium-btn" style="width: 100%; display: block; text-decoration: none; text-align: center; background: #f4f5f7; color: #111; padding: 10px; font-size: 11px; border-radius: 4px; border: 1px solid #eee; margin-top: auto;">VIEW DETAILS</a>
                </div>
                <div style="background: #f4f5f7; padding: 12px; text-align: center; font-size: 13px; font-weight: 800;">${displayDate}${suffixMonth} 2026</div>
            </div>
            `;
        }).join('');
    }

    function renderFeaturedSidebar(premiumEvents) {
        const grid = document.getElementById('featuredSidebarGrid');
        const section = document.getElementById('featuredSidebarSection');
        if (!grid || !section) return;
        if (premiumEvents.length === 0) { section.style.display = 'none'; return; }
        section.style.display = 'block';
        grid.innerHTML = premiumEvents.slice(0, 3).map(e => `
            <a href="event-detail.php?id=${e.id}" style="text-decoration: none; display: flex; gap: 15px; align-items: center; background: white; padding: 12px; border-radius: 12px; border: 1px solid #eee; margin-bottom: 12px;">
                <img src="${resolveEventImageUrl(getEventDisplayImage(e))}" onerror="this.onerror=null; this.src='${resolveEventImageUrl(getCategoryFallbackImage(e.category, e.id))}'" style="width: 60px; height: 60px; border-radius: 8px; object-fit: cover;">
                <div style="flex: 1;">
                    <span style="font-size: 9px; font-weight: 800; color: #f5a623; text-transform: uppercase;">FEATURED</span>
                    <h5 style="margin: 2px 0; color: #111; font-size: 13px;">${e.title}</h5>
                </div>
            </a>
        `).join('');
    }

    function getBadgeColor(cat) {
        const colors = { 'FESTIVAL': '#6bb33b', 'SPORTS': '#285da1', 'ARTS & CULTURE': '#cc3333', 'NATURE': '#5a3ba1' };
        return colors[cat] || '#666';
    }

    window.deleteEvent = async function (id) {
        if (!confirm('Are you sure you want to delete this event?')) return;
        try {
            const res = await fetch(`../api/v1/events.php?id=${id}`, { method: 'DELETE' });
            if ((await res.json()).success) fetchEvents();
        } catch (err) { console.error(err); }
    };

    init();
});