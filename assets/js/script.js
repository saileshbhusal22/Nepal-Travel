document.addEventListener('DOMContentLoaded', () => {
    // 1. Sticky Header Logic
    const header = document.querySelector('header');

    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.remove('transparent');
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
            header.classList.add('transparent');
        }
    });

    // Initialize header state on load
    if (window.scrollY > 50) {
        header.classList.remove('transparent');
        header.classList.add('scrolled');
    }

    // 2. Mobile Menu Toggle (Basic implementation)
    const mobileMenuBtn = document.querySelector('.mobile-menu-btn');
    const navLinks = document.querySelector('.nav-links');

    if (mobileMenuBtn && navLinks) {
        mobileMenuBtn.addEventListener('click', () => {
            // In a full implementation, this would toggle a slide-out menu
            // For now, we'll just alert that this is where the mobile menu logic goes
            alert("Mobile menu clicked! This would open the responsive side-drawer.");
        });
    }

    // 3. Smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });

    // 4. Newsletter form submission prevent default
    const newsletterForm = document.querySelector('.newsletter-form-refined');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const inputField = document.querySelector('.newsletter-input-refined');
            const input = inputField.value;
            if (input) {
                alert(`Thank you for subscribing with: ${input}!`);
                inputField.value = '';
            }
        });
    }

    // 4b. Category Navigation Logic
    const categoryLinks = document.querySelectorAll('.cat-link');
    categoryLinks.forEach(link => {
        link.addEventListener('click', function () {
            categoryLinks.forEach(l => l.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // 5. Interactive SVG Map Logic
    const mapContainer = document.getElementById('nepal-svg-container');
    const provinceTitle = document.getElementById('province-title');
    const provinceDesc = document.getElementById('province-desc');
    const provinceImg = document.getElementById('province-img');

    if (mapContainer) {
        const svgElement = mapContainer.querySelector('svg');
        if (svgElement) {
            // Ensure SVG scales properly
            svgElement.style.width = '100%';
            svgElement.style.height = 'auto';

            const paths = svgElement.querySelectorAll('path');

            // Load district data
            fetch('../data/districts.json')
                .then(response => response.json())
                .then(data => {
                    const { descriptions, categories } = data;

                    const getDistrictImage = (districtName) => {
                        if (categories.mountains.includes(districtName)) return 'images/hero_nepal.png';
                        if (categories.wildlife.includes(districtName)) return 'images/chitwan_rhino.png';
                        if (categories.heritage.includes(districtName)) return 'images/bhaktapur_temple.png';
                        if (categories.lakes.includes(districtName)) return 'images/pokhara_lake.png';
                        return 'images/annapurna_trek.png';
                    };

                    paths.forEach((path) => {
                        const handleInteraction = () => {
                            const name = path.getAttribute('data-name') || "Unknown";
                            const upperName = name.toUpperCase().trim();

                            // Get description or fallback
                            const desc = descriptions[upperName]
                                ? descriptions[upperName]
                                : `Experience the authentic local culture, scenic landscapes, and the warm hospitality of ${name} District.`;

                            // Switch content
                            if (provinceTitle) provinceTitle.textContent = name + " District";
                            if (provinceDesc) provinceDesc.textContent = desc;

                            // Optional Image update if element exists
                            if (provinceImg) {
                                const imgSrc = getDistrictImage(upperName);
                                provinceImg.src = imgSrc;
                                provinceImg.style.display = 'block';
                            }

                            // Visual feedback on map
                            paths.forEach(p => p.classList.remove('active'));
                            path.classList.add('active');
                        };

                        path.addEventListener('mouseover', handleInteraction);
                        path.addEventListener('click', (e) => {
                            e.preventDefault();
                            handleInteraction();
                            // Scroll to details on mobile if needed
                            if (window.innerWidth < 768) {
                                document.querySelector('.province-card').scrollIntoView({ behavior: 'smooth', block: 'center' });
                            }
                        });
                    });
                })
                .catch(err => console.error('Error loading district data:', err));
        }
    }

    // 6. 360° Panorama Switching Logic
    const panTabs = document.querySelectorAll('.pan-tab');
    const panIframe = document.getElementById('pan-iframe');
    const panTitle = document.getElementById('pan-title');
    const panDesc = document.getElementById('pan-desc');

    const panoramaData = {
        kathmandu: {
            title: "Kathmandu Durbar Square",
            desc: "The historic seat of the former Kathmandu Kingdom, this UNESCO site is a breathtaking ensemble of ancient temples, sun-drenched courtyards, and traditional Newari architecture.",
            url: "https://maps.google.com/maps?q=Kathmandu+Durbar+Square,+Nepal&t=k&z=17&ie=UTF8&iwloc=&output=embed"
        },
        swayambhunath: {
            title: "Swayambhunath Stupa",
            desc: "Perched atop a hill, the 'Monkey Temple' is one of the holiest Buddhist sites in Nepal, offering panoramic views of the Kathmandu Valley and a deeply spiritual atmosphere.",
            url: "https://maps.google.com/maps?q=Swayambhunath+Stupa,+Kathmandu,+Nepal&t=k&z=17&ie=UTF8&iwloc=&output=embed"
        },
        lumbini: {
            title: "Lumbini (Birthplace of Buddha)",
            desc: "A UNESCO World Heritage site of immense global significance, Lumbini is the birthplace of Siddhartha Gautama, who later became the Buddha. It's a place of peace and profound history.",
            url: "https://maps.google.com/maps?q=Lumbini,+Nepal&t=k&z=15&ie=UTF8&iwloc=&output=embed"
        },
        everest: {
            title: "Everest Base Camp View",
            desc: "Experience the sheer scale of the Himalayas from Gorak Shep, looking towards Everest Base Camp. This high-altitude panorama captures the raw beauty of the world's most famous peaks.",
            url: "https://maps.google.com/maps?q=Everest+Base+Camp,+Nepal&t=k&z=14&ie=UTF8&iwloc=&output=embed"
        },
        pashupatinath: {
            title: "Pashupatinath Temple",
            desc: "One of the most sacred Hindu temples in the world, Pashupatinath sits on the banks of the Bagmati River. This UNESCO site is a sprawling collection of temples, ashrams, and cremation ghats steeped in centuries of devotion.",
            url: "https://maps.google.com/maps?q=Pashupatinath+Temple,+Kathmandu,+Nepal&t=k&z=17&ie=UTF8&iwloc=&output=embed"
        },
        boudhanath: {
            title: "Boudhanath Stupa",
            desc: "One of the largest spherical stupas in the world, Boudhanath is the heart of Tibetan Buddhism in Nepal. Its all-seeing eyes of the Buddha gaze serenely across the Kathmandu Valley, surrounded by monasteries and prayer wheels.",
            url: "https://maps.google.com/maps?q=Boudhanath+Stupa,+Kathmandu,+Nepal&t=k&z=17&ie=UTF8&iwloc=&output=embed"
        }
    };

    panTabs.forEach(tab => {
        tab.addEventListener('click', function () {
            const site = this.getAttribute('data-site');
            const data = panoramaData[site];

            if (data) {
                // Update tabs active state
                panTabs.forEach(t => t.classList.remove('active'));
                this.classList.add('active');

                // Update content with a slight fade effect
                const infoBox = document.getElementById('pan-info');
                infoBox.style.opacity = '0.3';
                infoBox.style.transform = 'translateY(10px)';

                setTimeout(() => {
                    panIframe.src = data.url;
                    panTitle.textContent = data.title;
                    panDesc.textContent = data.desc;

                    infoBox.style.opacity = '1';
                    infoBox.style.transform = 'translateY(0)';
                }, 300);
            }
        });
    });
});