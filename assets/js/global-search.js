// Global Search Portal Functionality
document.addEventListener("DOMContentLoaded", function () {
  // Get DOM elements
  const searchPortal = document.getElementById("searchPortal");
  const openSearchBtn = document.getElementById("openSearchBtn");
  const openSearchBtn2 = document.getElementById("openSearchBtn2");
  const closeSearchBtn = document.getElementById("closeSearch");
  const searchInput = document.getElementById("globalSearchInput");
  const clearSearchBtn = document.getElementById("clearSearchInput");
  const searchPulse = document.getElementById("searchPulse");
  const resultsGrid = document.getElementById("searchResultsGrid");
  const searchTabs = document.querySelectorAll(".search-tab-btn");
  const suggestionsPanel = document.getElementById("suggestionsPanel");
  const suggestionChips = document.querySelectorAll(".suggestion-chip");

  let currentTab = "all";
  let searchResults = {
    all: [],
    experiences: [],
    ideas: [],
    deals: [],
    events: [],
  };

  // Open search portal
  function openSearchPortal() {
    searchPortal.classList.add("active");
    searchInput.focus();
    resultsGrid.innerHTML = "";
    suggestionsPanel.style.display = "block";
  }

  // Close search portal
  function closeSearchPortal() {
    searchPortal.classList.remove("active");
    searchInput.value = "";
    clearSearchBtn.classList.remove("visible");
    searchPulse.classList.remove("active");
    resultsGrid.innerHTML = "";
    suggestionsPanel.style.display = "block";
  }

  // Close on background click
  searchPortal.addEventListener("click", function (e) {
    if (e.target === searchPortal) {
      closeSearchPortal();
    }
  });

  // Open button listeners
  if (openSearchBtn) {
    openSearchBtn.addEventListener("click", function (e) {
      e.preventDefault();
      openSearchPortal();
    });
  }

  if (openSearchBtn2) {
    openSearchBtn2.addEventListener("click", function (e) {
      e.preventDefault();
      openSearchPortal();
    });
  }

  // Close button listener
  if (closeSearchBtn) {
    closeSearchBtn.addEventListener("click", closeSearchPortal);
  }

  // Clear search input
  if (clearSearchBtn) {
    clearSearchBtn.addEventListener("click", function () {
      searchInput.value = "";
      searchResults = { all: [], experiences: [], ideas: [], deals: [], events: [] };
      clearSearchBtn.classList.remove("visible");
      searchPulse.classList.remove("active");
      resultsGrid.innerHTML = "";
      suggestionsPanel.style.display = "block";
      updateResultCounts();
    });
  }

  // Search input listener
  searchInput.addEventListener("input", function () {
    const query = this.value.trim().toLowerCase();

    if (query.length === 0) {
      clearSearchBtn.classList.remove("visible");
      searchResults = { all: [], experiences: [], ideas: [], deals: [], events: [] };
      resultsGrid.innerHTML = "";
      suggestionsPanel.style.display = "block";
      updateResultCounts();
      return;
    }

    if (query.length > 0) {
      clearSearchBtn.classList.add("visible");
      searchPulse.classList.add("active");
      suggestionsPanel.style.display = "none";
    }

    // Simulate search delay
    setTimeout(() => {
      performSearch(query);
      searchPulse.classList.remove("active");
    }, 500);
  });

  // Perform search
  async function performSearch(query) {
    try {
      const response = await fetch(`/Nepal-Travel/api/v1/search.php?q=${encodeURIComponent(query)}`);
      const data = await response.json();
      
      if (data.success && data.results) {
        searchResults = {
          all: [...data.results.experiences, ...data.results.ideas, ...data.results.deals, ...data.results.events],
          experiences: data.results.experiences,
          ideas: data.results.ideas,
          deals: data.results.deals,
          events: data.results.events,
        };
      } else {
        searchResults = { all: [], experiences: [], ideas: [], deals: [], events: [] };
      }
    } catch (error) {
      console.error("Search error:", error);
      searchResults = { all: [], experiences: [], ideas: [], deals: [], events: [] };
    }

    // Update counts
    updateResultCounts();

    // Display results for current tab
    displayResults(currentTab);

    // Save to recent searches
    saveRecentSearch(query);
  }

  // Update result counts
  function updateResultCounts() {
    let allCount = searchResults.experiences.length +
      searchResults.ideas.length +
      searchResults.deals.length +
      searchResults.events.length;
    
    document.getElementById("count-all").textContent = allCount;
    document.getElementById("count-experiences").textContent =
      searchResults.experiences.length;
    document.getElementById("count-ideas").textContent =
      searchResults.ideas.length;
    document.getElementById("count-deals").textContent =
      searchResults.deals.length;
    
    const countEventsEl = document.getElementById("count-events");
    if (countEventsEl) countEventsEl.textContent = searchResults.events.length;
  }

  // Display results
  function displayResults(tab) {
    resultsGrid.innerHTML = "";

    // Check if there are any results
    const totalResults =
      searchResults.experiences.length +
      searchResults.ideas.length +
      searchResults.deals.length +
      searchResults.events.length;

    if (totalResults === 0) {
      resultsGrid.innerHTML = `
                <div class="search-empty-state">
                    <h3>No results found</h3>
                    <p>Try searching for different keywords</p>
                </div>
            `;
      return;
    }

    // Display EXPERIENCES section
    if (searchResults.experiences.length > 0 && (tab === 'all' || tab === 'experiences')) {
      const experienceSection = document.createElement("div");
      experienceSection.className = "result-category";

      const experienceTitle = document.createElement("div");
      experienceTitle.className = "result-category-title";
      experienceTitle.textContent = "COMMUNITY STORIES";
      experienceSection.appendChild(experienceTitle);

      const experienceScrollContainer = document.createElement("div");
      experienceScrollContainer.className = "category-scroll-container";

      searchResults.experiences.forEach((item) => {
        const card = document.createElement("div");
        card.className = "search-result-card search-result-card-horizontal";
        card.style.cursor = "pointer";
        card.innerHTML = `
                  <img src="${item.image}" alt="${item.title}" class="search-result-image" onerror="this.src='/Nepal-Travel/images/placeholder.jpg'">
                  <div class="search-result-content">
                      <div class="search-result-badge">${item.type}</div>
                      <div class="search-result-title">${item.title}</div>
                  </div>
              `;
        card.addEventListener("click", function () {
          if (item.link) {
            window.location.href = item.link;
          }
        });
        experienceScrollContainer.appendChild(card);
      });

      experienceSection.appendChild(experienceScrollContainer);
      resultsGrid.appendChild(experienceSection);
    }

    // Display IDEAS section
    if (searchResults.ideas.length > 0 && (tab === 'all' || tab === 'ideas')) {
      const ideasSection = document.createElement("div");
      ideasSection.className = "result-category";

      const ideasTitle = document.createElement("div");
      ideasTitle.className = "result-category-title";
      ideasTitle.textContent = "TRAVEL IDEAS";
      ideasSection.appendChild(ideasTitle);

      const ideasContainer = document.createElement("div");
      ideasContainer.className = "category-vertical-container";

      searchResults.ideas.forEach((item) => {
        const card = document.createElement("div");
        card.className = "search-result-card search-result-card-vertical";
        card.style.cursor = "pointer";
        card.innerHTML = `
                  <img src="${item.image}" alt="${item.title}" class="search-result-image" onerror="this.src='/Nepal-Travel/images/placeholder.jpg'">
                  <div class="search-result-content">
                      <div class="search-result-badge">${item.type}</div>
                      <div class="search-result-title">${item.title}</div>
                  </div>
              `;
        card.addEventListener("click", function () {
          if (item.link) {
            window.location.href = item.link;
          }
        });
        ideasContainer.appendChild(card);
      });

      ideasSection.appendChild(ideasContainer);
      resultsGrid.appendChild(ideasSection);
    }

    // Display DEALS section
    if (searchResults.deals.length > 0 && (tab === 'all' || tab === 'deals')) {
      const dealsSection = document.createElement("div");
      dealsSection.className = "result-category";

      const dealsTitle = document.createElement("div");
      dealsTitle.className = "result-category-title";
      dealsTitle.textContent = "HOT DEALS";
      dealsSection.appendChild(dealsTitle);

      const dealsContainer = document.createElement("div");
      dealsContainer.className = "category-vertical-container";

      searchResults.deals.forEach((item) => {
        const card = document.createElement("div");
        card.className = "search-result-card search-result-card-vertical";
        card.style.cursor = "pointer";
        card.innerHTML = `
                  <img src="${item.image}" alt="${item.title}" class="search-result-image" onerror="this.src='/Nepal-Travel/images/placeholder.jpg'">
                  <div class="search-result-content">
                      <div class="search-result-badge">${item.type}</div>
                      <div class="search-result-title">${item.title}</div>
                  </div>
              `;
        card.addEventListener("click", function () {
          if (item.link) {
            window.location.href = item.link;
          }
        });
        dealsContainer.appendChild(card);
      });

      dealsSection.appendChild(dealsContainer);
      resultsGrid.appendChild(dealsSection);
    }

    // Display EVENTS section
    if (searchResults.events && searchResults.events.length > 0 && (tab === 'all' || tab === 'events')) {
      const eventsSection = document.createElement("div");
      eventsSection.className = "result-category";

      const eventsTitle = document.createElement("div");
      eventsTitle.className = "result-category-title";
      eventsTitle.textContent = "EVENTS & HAPPENINGS";
      eventsSection.appendChild(eventsTitle);

      const eventsContainer = document.createElement("div");
      eventsContainer.className = "category-vertical-container";

      searchResults.events.forEach((item) => {
        const card = document.createElement("div");
        card.className = "search-result-card search-result-card-vertical";
        card.style.cursor = "pointer";
        card.innerHTML = `
                  <img src="${item.image}" alt="${item.title}" class="search-result-image" onerror="this.src='/Nepal-Travel/images/placeholder.jpg'">
                  <div class="search-result-content">
                      <div class="search-result-badge">${item.type}</div>
                      <div class="search-result-title">${item.title}</div>
                  </div>
              `;
        card.addEventListener("click", function () {
          if (item.link) {
            window.location.href = item.link;
          }
        });
        eventsContainer.appendChild(card);
      });

      eventsSection.appendChild(eventsContainer);
      resultsGrid.appendChild(eventsSection);
    }
  }

  // Tab switching
  searchTabs.forEach((tab) => {
    tab.addEventListener("click", function () {
      // Update active tab
      searchTabs.forEach((t) => t.classList.remove("active"));
      this.classList.add("active");

      currentTab = this.dataset.tab;
      // Always display all categories regardless of tab
      displayResults("all");
    });
  });

  // Suggestion chip click handler
  suggestionChips.forEach((chip) => {
    chip.addEventListener("click", function () {
      searchInput.value = this.textContent.trim();
      clearSearchBtn.classList.add("visible");
      searchInput.dispatchEvent(new Event("input"));
    });
  });

  // Save recent searches
  function saveRecentSearch(query) {
    let recentSearches =
      JSON.parse(localStorage.getItem("recentSearches")) || [];

    // Remove duplicate if exists
    recentSearches = recentSearches.filter((s) => s !== query);

    // Add to beginning
    recentSearches.unshift(query);

    // Keep only last 5
    recentSearches = recentSearches.slice(0, 5);

    localStorage.setItem("recentSearches", JSON.stringify(recentSearches));
    updateRecentSearches();
  }

  // Update recent searches display
  function updateRecentSearches() {
    const recentSearches =
      JSON.parse(localStorage.getItem("recentSearches")) || [];
    const recentChips = document.getElementById("recentSearchesChips");
    const recentBlock = document.getElementById("recentSearchesBlock");

    if (recentSearches.length === 0) {
      recentBlock.style.display = "none";
      return;
    }

    recentBlock.style.display = "block";
    recentChips.innerHTML = "";

    recentSearches.forEach((search) => {
      const chip = document.createElement("span");
      chip.className = "suggestion-chip";
      chip.textContent = search;
      chip.style.cursor = "pointer";
      chip.addEventListener("click", function () {
        searchInput.value = search;
        clearSearchBtn.classList.add("visible");
        searchInput.dispatchEvent(new Event("input"));
      });
      recentChips.appendChild(chip);
    });
  }

  // Initialize recent searches on load
  updateRecentSearches();

  // Close on Escape key
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && searchPortal.classList.contains("active")) {
      closeSearchPortal();
    }
  });
});
