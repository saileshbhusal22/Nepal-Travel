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
  };

  // Mock data for search results
  const mockData = {
    experiences: [
      {
        id: 1,
        title: "Everest Base Camp Trek",
        image: "/Nepal-Travel/images/everest_trek.png",
        type: "EXPERIENCE",
        link: "/Nepal-Travel/Public/experience.php",
      },
      {
        id: 2,
        title: "Pokhara Adventure",
        image: "/Nepal-Travel/images/pokhara_lake.png",
        type: "EXPERIENCE",
        link: "/Nepal-Travel/Public/experience.php",
      },
      {
        id: 3,
        title: "Kathmandu Cultural Tour",
        image: "/Nepal-Travel/images/bhaktapur_temple.png",
        type: "EXPERIENCE",
        link: "/Nepal-Travel/Public/experience.php",
      },
      {
        id: 10,
        title: "Chitwan Wildlife Safari",
        image: "/Nepal-Travel/images/chitwan_rhino.png",
        type: "EXPERIENCE",
        link: "/Nepal-Travel/Public/experience.php",
      },
      {
        id: 11,
        title: "Annapurna Trek",
        image: "/Nepal-Travel/images/annapurna_trek.png",
        type: "EXPERIENCE",
        link: "/Nepal-Travel/Public/experience.php",
      },
    ],
    ideas: [
      {
        id: 4,
        title: "7-Day Nepal Adventure",
        image: "/Nepal-Travel/images/annapurna_trek.png",
        type: "IDEA",
        link: "/Nepal-Travel/Public/travel-ideas.php",
      },
      {
        id: 5,
        title: "Budget Travel Guide",
        image: "/Nepal-Travel/images/food_drinks_nepal.png",
        type: "IDEA",
        link: "/Nepal-Travel/Public/travel-ideas.php",
      },
      {
        id: 6,
        title: "Best Time to Visit",
        image: "/Nepal-Travel/images/city_excitement_nepal.png",
        type: "IDEA",
        link: "/Nepal-Travel/Public/travel-ideas.php",
      },
      {
        id: 12,
        title: "Family Fun in Nepal",
        image: "/Nepal-Travel/images/family_fun_nepal.png",
        type: "IDEA",
        link: "/Nepal-Travel/Public/travel-ideas.php",
      },
    ],
    deals: [
      {
        id: 7,
        title: "Mountain Trek Package - 40% OFF",
        image: "/Nepal-Travel/images/everest_trek.png",
        type: "DEAL",
        link: "/Nepal-Travel/Public/deals-and-packages.php",
      },
      {
        id: 8,
        title: "Luxury Resort Stay - Early Bird",
        image: "/Nepal-Travel/images/kathmandu_night_hero.png",
        type: "DEAL",
        link: "/Nepal-Travel/Public/deals-and-packages.php",
      },
      {
        id: 9,
        title: "Group Tour Discount",
        image: "/Nepal-Travel/images/chitwan_rhino.png",
        type: "DEAL",
        link: "/Nepal-Travel/Public/deals-and-packages.php",
      },
      {
        id: 13,
        title: "Lumbini Pilgrimage Package",
        image: "/Nepal-Travel/images/lumbini_temple.png",
        type: "DEAL",
        link: "/Nepal-Travel/Public/deals-and-packages.php",
      },
      {
        id: 14,
        title: "Nepal Honeymoon Package - 35% Discount",
        image: "/Nepal-Travel/images/kathmandu_night_hero.png",
        type: "PACKAGE",
        link: "/Nepal-Travel/Public/deals-and-packages.php",
      },
      {
        id: 15,
        title: "5-Day Kathmandu Valley Tour",
        image: "/Nepal-Travel/images/bhaktapur_temple.png",
        type: "PACKAGE",
        link: "/Nepal-Travel/Public/deals-and-packages.php",
      },
      {
        id: 16,
        title: "Pokhara Lake & Mountain Package",
        image: "/Nepal-Travel/images/pokhara_lake.png",
        type: "PACKAGE",
        link: "/Nepal-Travel/Public/deals-and-packages.php",
      },
    ],
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
  function performSearch(query) {
    searchResults = {
      all: [],
      experiences: [],
      ideas: [],
      deals: [],
    };

    // Search through experiences
    mockData.experiences.forEach((item) => {
      if (item.title.toLowerCase().includes(query)) {
        searchResults.experiences.push(item);
        searchResults.all.push(item);
      }
    });

    // Search through ideas
    mockData.ideas.forEach((item) => {
      if (item.title.toLowerCase().includes(query)) {
        searchResults.ideas.push(item);
        searchResults.all.push(item);
      }
    });

    // Search through deals
    mockData.deals.forEach((item) => {
      if (item.title.toLowerCase().includes(query)) {
        searchResults.deals.push(item);
        searchResults.all.push(item);
      }
    });

    // Update counts
    updateResultCounts();

    // Display results for current tab
    displayResults(currentTab);

    // Save to recent searches
    saveRecentSearch(query);
  }

  // Update result counts
  function updateResultCounts() {
    document.getElementById("count-all").textContent = searchResults.all.length;
    document.getElementById("count-experiences").textContent =
      searchResults.experiences.length;
    document.getElementById("count-ideas").textContent =
      searchResults.ideas.length;
    document.getElementById("count-deals").textContent =
      searchResults.deals.length;
  }

  // Display results
  function displayResults(tab) {
    resultsGrid.innerHTML = "";
    const results = searchResults[tab] || [];

    if (results.length === 0) {
      resultsGrid.innerHTML = `
                <div class="search-empty-state">
                    <h3>No results found</h3>
                    <p>Try searching for different keywords</p>
                </div>
            `;
      return;
    }

    results.forEach((item) => {
      const card = document.createElement("div");
      card.className = "search-result-card";
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
      resultsGrid.appendChild(card);
    });
  }

  // Tab switching
  searchTabs.forEach((tab) => {
    tab.addEventListener("click", function () {
      // Update active tab
      searchTabs.forEach((t) => t.classList.remove("active"));
      this.classList.add("active");

      currentTab = this.dataset.tab;
      displayResults(currentTab);
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
