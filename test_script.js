const fs = require('fs');
const jsdom = require("jsdom");
const { JSDOM } = jsdom;

const html = fs.readFileSync('Public/event-detail.php', 'utf8');
const dom = new JSDOM(html);
const document = dom.window.document;

const e = {"id":19,"title":"Art Competition","tags":null,"language":"Both","age_group":"All Ages","description":"event about to hapen for a charity.","what_to_expect":null,"image_path":"images\/placeholder_event.jpg","gallery_images":null,"category":"ARTS & CULTURE","location":"fewa lake","full_address":null,"region":null,"event_date":"1 June","month":"JUN","is_featured":0,"created_at":"2026-05-14 09:01:41","is_private":0,"user_id":4,"subscription_id":null,"is_premium":0,"ticket_price":"100","seats":0,"selling_fast_threshold":80,"early_bird_text":null,"featured_badge_text":"FEATURED","ticket_link":"0","ticket_status":"Available","venue_name":null,"google_maps_link":null,"is_paid":0,"price_npr":"0.00","unlimited_seats":1,"registration_url":null,"homepage_spotlight":0,"raffle_enabled":0,"raffle_draw_time":null,"raffle_prize_1":null,"raffle_prize_2":null,"raffle_entry_fee":"0.00","free_parking":0,"organizer_contact":null,"organizer_website":null,"organizer_name":null,"organizer_email":null,"organizer_facebook":null,"organizer_instagram":null,"start_date":null,"start_time":null,"end_date":null,"end_time":null,"is_recurring":0,"recurring_frequency":null,"is_international":0,"source":"local"};

try {
            let imgPath = e.image_path || '../images/placeholder_event.jpg';
            if (imgPath.startsWith('images/')) {
                imgPath = '../' + imgPath;
            }
            document.getElementById('dImg').src = imgPath;
            document.getElementById('dTitle').textContent = e.title || 'Untitled Event';
            document.getElementById('dCatBadge').textContent = e.category || 'General';
            document.getElementById('dCatBread').textContent = e.category || 'General';
            document.getElementById('dLocHero').textContent = e.region || e.location || 'Nepal';
            
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
                if (d.toUpperCase().includes(m.toUpperCase())) {
                    dateStr = `${d} 2026`.trim();
                } else {
                    dateStr = `${d} ${m} 2026`.trim();
                }
            }
            document.getElementById('dDateHero').textContent = dateStr;
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

            const timeStr = (e.start_time && e.start_time !== '00:00:00' && e.end_time && e.end_time !== '00:00:00') 
                ? ` • ${formatTime(e.start_time)} – ${formatTime(e.end_time)}` 
                : "";
            document.getElementById('dDateTimeFormatted').textContent = (dateStr + timeStr).trim();
            document.getElementById('dVenueFull').textContent = e.venue_name || e.location || 'Nepal';
            document.getElementById('dAdmissionPrice').textContent = e.is_paid == 1 ? `NPR ${parseFloat(e.price_npr || 0).toLocaleString()}` : 'Free Admission';
            
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
    
            if (startDate && !isNaN(startDate)) {
                const calStart = (e.start_date || "").replace(/-/g, '') + (e.start_time ? 'T' + e.start_time.replace(/:/g, '') + '00Z' : '');
                const calEnd = (e.end_date || e.start_date || "").replace(/-/g, '') + (e.end_time ? 'T' + e.end_time.replace(/:/g, '') + '00Z' : '');
                document.getElementById('dAddToCal').href = `https://www.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(e.title || "")}&dates=${calStart}/${calEnd}&details=${encodeURIComponent(e.description || "")}&location=${encodeURIComponent(e.location || "")}`;
            }

            if (e.seats > 0 && !e.unlimited_seats) {
                document.getElementById('dSeatsBox').style.display = 'block';
                const occupancy = Math.floor(Math.random() * 40) + 60; 
                const left = Math.floor(e.seats * (1 - occupancy/100));
                document.getElementById('dSeatsProgress').style.width = occupancy + '%';
                document.getElementById('dSeatsPercent').textContent = occupancy + '%';
                document.getElementById('dSeatsText').textContent = `${left.toLocaleString()} / ${parseInt(e.seats).toLocaleString()} seats remaining`;
            }

            if (e.is_premium == 1) {
                document.getElementById('premiumCTA').style.display = 'block';
                document.getElementById('dTicketLink').href = e.ticket_link || e.registration_url || '#';
                document.getElementById('badgeFeatured').style.display = 'flex';
            }
            if (e.free_parking == 1) document.getElementById('badgeParking').style.display = 'flex';

            document.getElementById('dDesc').innerHTML = (e.description || 'No description available.').replace(/\n/g, '<br>');
            document.getElementById('dLang').textContent = e.language || 'English & Nepali';
            document.getElementById('dAge').textContent = e.age_group || 'All Ages';
            document.getElementById('dType').textContent = e.is_international == 1 ? 'International Event' : 'Local Community Event';

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

            let galleryList = [];
            if (e.gallery_images && e.gallery_images.trim() !== "") {
                galleryList = e.gallery_images.split(',').map(img => {
                    let trimmed = img.trim();
                    return trimmed.startsWith('images/') ? '../' + trimmed : trimmed;
                });
            } else {
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

            if (e.organizer_name) {
                document.getElementById('dOrgBox').style.display = 'block';
                document.getElementById('dOrgName').textContent = e.organizer_name;
                document.getElementById('dOrgEmailText').textContent = e.organizer_email || 'contact@organizer.com';
                document.getElementById('dOrgEmail').href = `mailto:${e.organizer_email || ''}`;
                document.getElementById('dOrgPhoneText').textContent = e.organizer_contact || '+977-1-4XXXXXX';
                document.getElementById('dOrgPhone').href = `tel:${e.organizer_contact || ''}`;
                document.getElementById('dOrgImg').src = `https://ui-avatars.com/api/?name=${encodeURIComponent(e.organizer_name)}&background=333&color=fff`;
            }

            const tagData = e.tags || e.category || "Event";
            const tags = tagData.split(',').map(t => t.trim());
            document.getElementById('dTags').innerHTML = tags.map(t => `<a href="events.php?q=${encodeURIComponent(t)}" class="tag-chip">#${t.toLowerCase().replace(/\s+/g, '') }</a>`).join('');

            const locQuery = encodeURIComponent(e.venue_name ? `${e.venue_name}, ${e.location || ''}` : (e.location || 'Nepal'));
            const mapSrc = `https://maps.google.com/maps?q=${locQuery}&t=&z=14&ie=UTF8&iwloc=&output=embed`;
            document.getElementById('dMapFrameFull').src = mapSrc;
            document.getElementById('dVenueMap').href = `https://www.google.com/maps/search/?api=1&query=${locQuery}`;

            const ticketUrl = e.registration_url || e.ticket_link;
            const isPaid = parseInt(e.is_paid || 0) === 1;
            
            if (isPaid || (ticketUrl && ticketUrl.trim() !== '')) {
                document.getElementById('premiumCTA').style.display = 'block';
                const ticketBtn = document.getElementById('dTicketLink');
                if (ticketBtn) {
                    const newTicketBtn = ticketBtn.cloneNode(true);
                    ticketBtn.parentNode.replaceChild(newTicketBtn, ticketBtn);
                }
            } else {
                document.getElementById('premiumCTA').style.display = 'none';
            }

            const qtyInput = document.getElementById('modalTicketQty');

            console.log("SUCCESS");
} catch(err) {
  console.log("ERROR: " + err.message);
}
