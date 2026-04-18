// Experience Filter Functionality
document.addEventListener('DOMContentLoaded', function() {
    const applyBtn = document.querySelector('.sidebar-filter .btn-primary');
    const regionSelect = document.querySelector('.custom-select');
    const checkboxes = document.querySelectorAll('.checkbox-label input[type="checkbox"]');
    const ideaCards = document.querySelectorAll('.idea-card');
    const resultsInfo = document.querySelector('.results-info');

    // Sample data for experiences (you can replace this with API calls)
    const experiences = [
        {
            id: 1,
            title: 'Ultimate Wellness & Yoga Retreat',
            region: 'Gandaki Zone',
            duration: '7 Days',
            categories: ['Spiritual & Wellness'],
            durationDays: '4-7',
            months: ['January', 'February', 'March', 'April', 'May', 'October', 'November', 'December']
        },
        {
            id: 2,
            title: 'Buddhist Monastary Cultural Immersion',
            region: 'Lumbini Province',
            duration: '5 Days',
            categories: ['Heritage & Culture'],
            durationDays: '4-7',
            months: ['January', 'February', 'March', 'April', 'October', 'November']
        },
        {
            id: 3,
            title: 'Shivapuri National Park Forest Bathing',
            region: 'Bagmati Zone',
            duration: '4 Days',
            categories: ['Nature & Wildlife', 'Spiritual & Wellness'],
            durationDays: '1-3',
            months: ['September', 'October', 'November', 'December', 'January']
        }
    ];

    // Make cards clickable
    ideaCards.forEach(card => {
        card.addEventListener('click', function() {
            // Add click feedback
            this.style.opacity = '0.9';
            setTimeout(() => {
                this.style.opacity = '1';
            }, 200);
            
            // You can add navigation or modal opening here
            const title = this.querySelector('.card-title').textContent;
            console.log('Clicked on:', title);
            // Example: window.location.href = '#experience-details';
        });
        
        // Add cursor pointer
        card.style.cursor = 'pointer';
    });

    // Apply filters button
    applyBtn.addEventListener('click', function() {
        filterExperiences();
    });

    // Real-time filtering on checkbox change
    checkboxes.forEach(checkbox => {
        checkbox.addEventListener('change', filterExperiences);
    });

    // Region select change
    regionSelect.addEventListener('change', filterExperiences);

    function filterExperiences() {
        const selectedRegion = regionSelect.value;
        const selectedCategories = Array.from(document.querySelectorAll('.filter-group:nth-child(3) .checkbox-label input:checked'))
            .map(cb => cb.nextElementSibling?.textContent || '')
            .filter(text => text);
        
        const selectedDurations = Array.from(document.querySelectorAll('.filter-group:nth-child(4) .checkbox-label input:checked'))
            .map(cb => cb.nextElementSibling?.textContent || '')
            .filter(text => text);
        
        const selectedMonths = Array.from(document.querySelectorAll('.filter-group:nth-child(5) .checkbox-label input:checked'))
            .map(cb => cb.nextElementSibling?.textContent || '')
            .filter(text => text);

        let visibleCount = 0;

        // Update card visibility
        ideaCards.forEach((card, index) => {
            const cardRegion = card.querySelector('.card-region')?.textContent.trim();
            const cardTitle = card.querySelector('.card-title')?.textContent.trim();
            
            const exp = experiences[index];
            if (!exp) return;

            let matches = true;

            // Check region filter
            if (selectedRegion !== 'All Regions' && cardRegion !== selectedRegion) {
                matches = false;
            }

            // Check category filter
            if (selectedCategories.length > 0) {
                const hasMatchingCategory = selectedCategories.some(cat => 
                    exp.categories.some(expCat => expCat.toLowerCase() === cat.toLowerCase())
                );
                if (!hasMatchingCategory) matches = false;
            }

            // Check duration filter
            if (selectedDurations.length > 0) {
                const durationMatch = selectedDurations.some(dur => {
                    if (dur.includes('Half')) return exp.durationDays === '0.5';
                    if (dur.includes('1 -')) return exp.durationDays === '1-3';
                    if (dur.includes('4 -')) return exp.durationDays === '4-7';
                    if (dur.includes('8 -')) return exp.durationDays === '8-14';
                    if (dur.includes('15+')) return exp.durationDays === '15+';
                    return true;
                });
                if (!durationMatch) matches = false;
            }

            // Check month filter
            if (selectedMonths.length > 0) {
                const hasMatchingMonth = selectedMonths.some(month => 
                    exp.months.includes(month)
                );
                if (!hasMatchingMonth) matches = false;
            }

            // Update card visibility
            if (matches) {
                card.style.display = 'block';
                card.style.opacity = '1';
                card.style.pointerEvents = 'auto';
                visibleCount++;
            } else {
                card.style.display = 'none';
                card.style.opacity = '0.5';
                card.style.pointerEvents = 'none';
            }
        });

        // Update results info
        if (resultsInfo) {
            resultsInfo.innerHTML = `<p>Showing <strong>${visibleCount}</strong> matching ${visibleCount === 1 ? 'experience' : 'experiences'}</p>`;
        }
    }

    // Initialize with default view
    filterExperiences();
});
