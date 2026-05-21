    
// Session status variables
const isLoggedIn     = 1;
const sessionUserId  = 1;
const sessionIsAdmin = 1;

function openTicketModal(e) {
    const modal = document.getElementById('ticketModal');
    if (!modal) return;
    
    document.getElementById('modalEventTitle').textContent = e.title || 'Untitled Event';
    document.getElementById('modalEventTitleInput').value = e.title || 'Untitled Event';
    document.getElementById('modalEventId').value = e.id;
    document.getElementById('modalEventDateInput').value = e.start_date || e.event_date || '';
    
    const isPaid = parseInt(e.is_paid || 0) === 1;
    const ticketPrice = isPaid ? parseFloat(e.price_npr || 0) : 0;
    
    document.getElementById('modalTicketPriceInput').value = ticketPrice;
    document.getElementById('modalAmountInput').value = ticketPrice;
    
    // Clear dynamic breakdown variables
    document.getElementById('modalTicketQty').value = 1;
    document.getElementById('modalQtyText').textContent = '1 ticket';
    
    if (isPaid) {
        document.getElementById('modalSinglePriceText').textContent = 'NPR ' + ticketPrice.toLocaleString('en-IN', { maximumFractionDigits: 0 });
        document.getElementById('modalTotalPriceText').textContent = 'NPR ' + ticketPrice.toLocaleString('en-IN', { maximumFractionDigits: 0 });
        document.getElementById('modalSubmitBtn').innerHTML = '<span class="esewa-btn-logo">e</span> Pay with eSewa';
        document.getElementById('modalSubmitBtn').className = 'modal-btn primary';
        document.getElementById('modalSubmitBtn').style.background = '#2b78e4';
        document.getElementById('modalSubmitBtn').style.color = 'white';
    } else {
        document.getElementById('modalSinglePriceText').textContent = 'Free Admission';
        document.getElementById('modalTotalPriceText').textContent = 'Free';
        document.getElementById('modalSubmitBtn').innerHTML = '🎟️ Get Free Ticket';
        document.getElementById('modalSubmitBtn').className = 'modal-btn secondary';
        document.getElementById('modalSubmitBtn').style.background = '#28a745';
        document.getElementById('modalSubmitBtn').style.color = 'white';
    }
    
    if (isLoggedIn) {
        document.getElementById('modalLoggedOutState').style.display = 'none';
        document.getElementById('ticketBookingForm').style.display = 'block';
    } else {
        document.getElementById('modalLoggedOutState').style.display = 'block';
        document.getElementById('modalLoginLink').href = `../user/login.php?redirect=Public/event-detail.php?id=${e.id}`;
        document.getElementById('ticketBookingForm').style.display = 'none';
    }
    
    modal.classList.add('active');
}

function closeTicketModal() {
    const modal = document.getElementById('ticketModal');
    if (modal) {
        modal.classList.remove('active');
    }
}

document.addEventListener('DOMContentLoaded', async () => {
    // Header Scroll Logic
    const header = document.querySelector('.site-header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    const urlParams = new URLSearchParams(window.location.search);
    const id = urlParams.get('id');

    if (!id) {
        window.location.href = 'events.php';
        return;
    }

    try {
        const res = await fetch(`../api/v1/events.php?id=${id}`);
        const data = await res.json();

        if (data.success) {
            const e = data.event;
            if (!e) throw new Error("Event data is empty");
            
            // Hero
            let imgPath = e.image_path || '../images/placeholder_event.jpg';
            if (imgPath.startsWith('images/')) {
                imgPath = '../' + imgPath;
            }
            document.getElementById('dImg').src = imgPath;
            document.getElementById('dTitle').textContent = e.title || 'Untitled Event';
            document.getElementById('dCatBadge').textContent = e.category || 'General';
            document.getElementById('dCatBread').textContent = e.category || 'General';
            document.getElementById('dLocHero').textContent = e.region || e.location || 'Nepal';
            
            // Date Formatting
            const startDate = e.start_date ? new Date(e.start_date) : null;
            const endDate = e.end_date ? new Date(e.end_date) : null;
            const options = { day: '2-digit', month: 'short', year: 'numeric' };
            
            let dateStr = "";
            if (startDate && !isNaN(startDate)) {
                const sStr = startDate.toLocaleDateString('en-GB', options).toUpperCase();
                if (endDate && !isNaN(endDate) && e.start_date !== e.end_date) {
                    const eStr = endDate.toLocaleDateString('en-GB', options).toUpperCase();
                    dateStr = `${sStr} - ${eStr}`;
                } else {
                    dateStr = sStr;
                }
            } else {
                let d = e.event_date || '';
                let m = e.month === 'ALL' ? '' : (e.month || '');
                // Fix: Avoid "15 SEP SEP" duplication
                if (d.toUpperCase().includes(m.toUpperCase())) {
                    dateStr = `${d} 2026`.trim();
                } else {
                    dateStr = `${d} ${m} 2026`.trim();
                }
            }
            document.getElementById('dDateHero').textContent = dateStr;
                // Badges
            const featBadge = document.getElementById('badgeFeatured');
            const parkBadge = document.getElementById('badgeParking');
            if (featBadge) {
                if (e.is_featured == 1) {
                    featBadge.style.display = 'flex';
                    featBadge.classList.add('badge-featured');
                    featBadge.innerHTML = `<i class="fas fa-star"></i> ${e.featured_badge_text || 'FEATURED'}`;
                } else {
                    featBadge.style.display = 'none';
                }
            }
            if (parkBadge) {
                if (e.free_parking == 1) {
                    parkBadge.style.display = 'flex';
                    parkBadge.classList.add('badge-parking');
                } else {
                    parkBadge.style.display = 'none';
                }
            }

            // Sidebar Logistics
            const timeStr = (e.start_time && e.start_time !== '00:00:00' && e.end_time && e.end_time !== '00:00:00') 
                ? ` • ${formatTime(e.start_time)} – ${formatTime(e.end_time)}` 
                : "";
            document.getElementById('dDateTimeFormatted').textContent = (dateStr + timeStr).trim();
            document.getElementById('dVenueFull').textContent = e.venue_name || e.location || 'Nepal';
            document.getElementById('dAdmissionPrice').textContent = e.is_paid == 1 ? `NPR ${parseFloat(e.price_npr || 0).toLocaleString()}` : 'Free Admission';
            
            // Seats & Progress
            const seatsBox = document.getElementById('dSeatsBox');
            if (seatsBox && e.seats > 0 && !e.unlimited_seats) {
                seatsBox.style.display = 'block';
                const totalSeats = parseInt(e.seats || 100);
                const occupancy = (parseInt(e.id) % 30) + 40; 
                const soldCount = Math.round((occupancy / 100) * totalSeats);
                
                const percentEl = document.getElementById('dSeatsPercent');
                const progressEl = document.getElementById('dSeatsProgress');
                const textEl = document.getElementById('dSeatsText');
                
                if (percentEl) percentEl.innerText = `${occupancy}% Full`;
                if (progressEl) {
                    progressEl.style.width = `${occupancy}%`;
                    progressEl.style.background = occupancy > 85 ? '#cc3333' : '#f5a623';
                }
                if (textEl) textEl.innerText = `${totalSeats - soldCount} Seats Remaining out of ${totalSeats}`;
            }
    
            // Add to Calendar Link
            if (startDate && !isNaN(startDate)) {
                const calStart = (e.start_date || "").replace(/-/g, '') + (e.start_time ? 'T' + e.start_time.replace(/:/g, '') + '00Z' : '');
                const calEnd = (e.end_date || e.start_date || "").replace(/-/g, '') + (e.end_time ? 'T' + e.end_time.replace(/:/g, '') + '00Z' : '');
                document.getElementById('dAddToCal').href = `https://www.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(e.title || "")}&dates=${calStart}/${calEnd}&details=${encodeURIComponent(e.description || "")}&location=${encodeURIComponent(e.location || "")}`;
            }

            // Seats Progress
            if (e.seats > 0 && !e.unlimited_seats) {
                document.getElementById('dSeatsBox').style.display = 'block';
                const occupancy = Math.floor(Math.random() * 40) + 60; 
                const left = Math.floor(e.seats * (1 - occupancy/100));
                document.getElementById('dSeatsProgress').style.width = occupancy + '%';
                document.getElementById('dSeatsPercent').textContent = occupancy + '%';
                document.getElementById('dSeatsText').textContent = `${left.toLocaleString()} / ${parseInt(e.seats).toLocaleString()} seats remaining`;
            }

            // CTA & Badges
            if (e.is_premium == 1) {
                document.getElementById('premiumCTA').style.display = 'block';
                document.getElementById('dTicketLink').href = e.ticket_link || e.registration_url || '#';
                document.getElementById('badgeFeatured').style.display = 'flex';
            }
            if (e.free_parking == 1) document.getElementById('badgeParking').style.display = 'flex';

            // Content Left
            document.getElementById('dDesc').innerHTML = (e.description || 'No description available.').replace(/\n/g, '<br>');
            document.getElementById('dLang').textContent = e.language || 'English & Nepali';
            document.getElementById('dAge').textContent = e.age_group || 'All Ages';
            document.getElementById('dType').textContent = e.is_international == 1 ? 'International Event' : 'Local Community Event';

            // Raffle
            if (e.raffle_enabled == 1) {
                document.getElementById('dRaffleBox').style.display = 'block';
                document.getElementById('dPrize1').textContent = e.raffle_prize_1 || 'Grand Surprise Prize';
                if (e.raffle_draw_time) {
                    const dTime = new Date(e.raffle_draw_time);
                    if (!isNaN(dTime)) {
                        document.getElementById('dDrawTime').textContent = `DRAW TIME: ${dTime.toLocaleString('en-GB', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }).toUpperCase()}`;
                    }
                }
            }

            // Gallery
            let galleryList = [];
            if (e.gallery_images && e.gallery_images.trim() !== "") {
                // Use ONLY uploaded gallery images if they exist
                galleryList = e.gallery_images.split(',').map(img => {
                    let trimmed = img.trim();
                    return trimmed.startsWith('images/') ? '../' + trimmed : trimmed;
                });
            } else {
                // Fallback to placeholders only if no gallery images were uploaded
                galleryList = [imgPath];
                const catImg = {
                    'ARTS & CULTURE': 'https://images.unsplash.com/photo-1544644181-1484b3fdfc62',
                    'FOOD & CUISINE': 'https://images.unsplash.com/photo-1504674900247-0877df9cc836',
                    'MUSIC & CONCERT': 'https://images.unsplash.com/photo-1459749411177-042180ce673c',
                    'FESTIVALS': 'https://images.unsplash.com/photo-1533174072545-7a4b6ad7a6c3',
                    'SPORTS': 'https://images.unsplash.com/photo-1574629810360-7efbbe195018',
                    'NATURE': 'https://images.unsplash.com/photo-1544735716-392fe2489ffa'
                };
                const catKey = (e.category || "").toUpperCase();
                const placeholder = catImg[catKey] || 'https://images.unsplash.com/photo-1544735716-392fe2489ffa';
                for(let i=0; i<5; i++) galleryList.push(`${placeholder}?sig=${i}`);
            }
            
            const gHtml = galleryList.map((src, i) => `
                <div class="gallery-item ${i === 0 ? 'wide' : ''}">
                    <img src="${src}" alt="Gallery ${i}">
                </div>
            `).join('');
            document.getElementById('dGallery').innerHTML = gHtml;

            // Organizer
            if (e.organizer_name) {
                document.getElementById('dOrgBox').style.display = 'block';
                document.getElementById('dOrgName').textContent = e.organizer_name;
                document.getElementById('dOrgEmailText').textContent = e.organizer_email || 'contact@organizer.com';
                document.getElementById('dOrgEmail').href = `mailto:${e.organizer_email || ''}`;
                document.getElementById('dOrgPhoneText').textContent = e.organizer_contact || '+977-1-4XXXXXX';
                document.getElementById('dOrgPhone').href = `tel:${e.organizer_contact || ''}`;
                document.getElementById('dOrgImg').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(e.organizer_name)}&background=333&color=fff`;
            }

            // Tags
            const tagData = e.tags || e.category || "Event";
            const tags = tagData.split(',').map(t => t.trim());
            document.getElementById('dTags').innerHTML = tags.map(t => `<a href="events.php?q=${encodeURIComponent(t)}" class="tag-chip">#${t.toLowerCase().replace(/\s+/g, '') }</a>`).join('');

            // Maps
            const locQuery = encodeURIComponent(e.venue_name ? `${e.venue_name}, ${e.location || ''}` : (e.location || 'Nepal'));
            const mapSrc = `https://maps.google.com/maps?q=${locQuery}&t=&z=14&ie=UTF8&iwloc=&output=embed`;
            document.getElementById('dMapFrameFull').src = mapSrc;
            document.getElementById('dVenueMap').href = `https://www.google.com/maps/search/?api=1&query=${locQuery}`;

            // Load Related Events
            loadRelatedEvents(e.category || "", e.id);

            // Ticket CTA
            const ticketUrl = e.registration_url || e.ticket_link;
            const isPaid = parseInt(e.is_paid || 0) === 1;
            
            if (isPaid || (ticketUrl && ticketUrl.trim() !== '')) {
                document.getElementById('premiumCTA').style.display = 'block';
                const ticketBtn = document.getElementById('dTicketLink');
                if (ticketBtn) {
                    // Clone to strip any prior standard link click events
                    const newTicketBtn = ticketBtn.cloneNode(true);
                    ticketBtn.parentNode.replaceChild(newTicketBtn, ticketBtn);
                    
                    newTicketBtn.addEventListener('click', (event) => {
                        event.preventDefault();
                        openTicketModal(e);
                    });
                }
            } else {
                document.getElementById('premiumCTA').style.display = 'none';
            }

            // Bind Ticket Quantity Input Dynamic Calculation
            const qtyInput = document.getElementById('modalTicketQty');
            if (qtyInput) {
                qtyInput.addEventListener('input', (event) => {
                    const qty = Math.max(1, parseInt(event.target.value) || 1);
                    const price = parseFloat(document.getElementById('modalTicketPriceInput').value || 0);
                    const total = price * qty;
                    
                    document.getElementById('modalQtyText').textContent = `${qty} ticket${qty > 1 ? 's' : ''}`;
                    if (price > 0) {
                        document.getElementById('modalTotalPriceText').textContent = 'NPR ' + total.toLocaleString('en-IN', { maximumFractionDigits: 0 });
                        document.getElementById('modalAmountInput').value = total.toFixed(2);
                    }
                });
            }
            showOwnerControls(e);

            document.getElementById('loadingState').style.display = 'none';
            document.getElementById('eventDetailContent').style.display = 'block';
            window.scrollTo(0,0);
        } else {
            alert('Event not found: ' + (data.message || 'Unknown error'));
            window.location.href = 'events.php';
        }
    } catch (err) {
        console.error("DEBUG ERROR:", err);
        alert('Failed to load event details: ' + err.message);
    }
});

function formatTime(timeStr) {
    if (!timeStr) return "";
    const [h, m] = timeStr.split(':');
    const hour = parseInt(h);
    const ampm = hour >= 12 ? 'PM' : 'AM';
    const h12 = hour % 12 || 12;
    return `${h12}:${m} ${ampm}`;
}

async function loadRelatedEvents(category, excludeId) {
    try {
        const res = await fetch(`../api/v1/events.php?category=${encodeURIComponent(category)}`);
        const data = await res.json();
        if (data.success) {
            const filtered = data.events.filter(ev => ev.id !== excludeId).slice(0, 3);
            const grid = document.getElementById('relatedEventsGrid');
            if (filtered.length === 0) {
                grid.innerHTML = '<p style="color:#999; font-size:14px;">No similar events found at the moment.</p>';
                return;
            }
            grid.innerHTML = filtered.map(ev => {
                let relatedImg = ev.image_path || '../images/placeholder_event.jpg';
                if (relatedImg.startsWith('images/')) {
                    relatedImg = '../' + relatedImg;
                }
                const rawDate = (ev.event_date && ev.event_date !== 'null') ? ev.event_date : '';
                const displayDate = rawDate || (ev.month !== 'ALL' ? ev.month : 'MAY');
                const suffixMonth = (rawDate && !rawDate.toUpperCase().includes(ev.month.toUpperCase())) ? ` ${ev.month}` : '';
                const dateStr = `${displayDate}${suffixMonth} 2026`.trim();

                return `
                    <a href="event-detail.php?id=${ev.id}" style="text-decoration:none; color:inherit;">
                        <div style="background:white; border-radius:16px; overflow:hidden; box-shadow:0 10px 30px rgba(0,0,0,0.05);">
                            <img src="${relatedImg}" onerror="this.src='../images/placeholder_event.jpg'" style="width:100%; height:180px; object-fit:cover;">
                            <div style="padding:20px;">
                                <span style="font-size:10px; font-weight:800; color:var(--accent); text-transform:uppercase;">${ev.category}</span>
                                <h4 style="font-size:16px; margin:8px 0; font-family:'Playfair Display',serif;">${ev.title}</h4>
                                <p style="font-size:12px; color:#888; margin:0;">${dateStr}</p>
                            </div>
                        </div>
                    </a>
                `;
            }).join('');
        }
    } catch (err) { console.error(err); }
}

function shareEvent(platform) {
    const url = window.location.href;
    const title = document.getElementById('dTitle').textContent;
    let shareUrl = "";
    if (platform === 'facebook') shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
    if (platform === 'whatsapp') shareUrl = `https://api.whatsapp.com/send?text=${encodeURIComponent(title + " " + url)}`;
    if (shareUrl) window.open(shareUrl, '_blank');
}
/* =========================================
   OWNER EVENT CONTROLS
========================================= */

let currentEventData = null;

/* Show Edit/Delete Buttons */

function showOwnerControls(eventData){

    currentEventData = eventData;

    if(!isLoggedIn) return;

    const ownerBar = document.getElementById('ownerActionBar');

    const isOwner =
        Number(eventData.user_id) === Number(sessionUserId);

    if (ownerBar && (isOwner || sessionIsAdmin)) {
        ownerBar.style.display = 'flex';
    }
}

function initOwnerControls() {
    const editBtn = document.getElementById('editEventBtn');
    const deleteBtn = document.getElementById('deleteEventBtn');
    const deleteModal = document.getElementById('deleteEventModal');
    const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
    const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');

    if (editBtn) {
        editBtn.addEventListener('click', () => {
            if (!currentEventData) return;
            window.location.href = `events.php?edit=${currentEventData.id}`;
        });
    }

    if (deleteBtn && deleteModal) {
        deleteBtn.addEventListener('click', () => deleteModal.classList.add('active'));
    }

    if (cancelDeleteBtn && deleteModal) {
        cancelDeleteBtn.addEventListener('click', () => deleteModal.classList.remove('active'));
    }

    if (confirmDeleteBtn) {
        confirmDeleteBtn.addEventListener('click', async () => {
            if (!currentEventData) return;

            try {
                const response = await fetch(
                    `../api/v1/events.php?id=${currentEventData.id}`,
                    { method: 'DELETE' }
                );
                const data = await response.json();

                if (data.success) {
                    alert('Event deleted successfully');
                    window.location.href = 'events.php';
                } else {
                    alert(data.message || 'Delete failed');
                }
            } catch (err) {
                console.error(err);
                alert('Something went wrong');
            }
        });
    }
}

initOwnerControls();
