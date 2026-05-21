document.addEventListener("DOMContentLoaded", function () {
  const searchTrigger = document.querySelector('a[href="#search-portal"]');
  const searchOverlay = document.getElementById("searchPortal");
  const searchClose = document.getElementById("closeSearch");
  const searchInput = document.getElementById("globalSearchInput");
  const clearInputBtn = document.getElementById("clearSearchInput");
  const pulse = document.getElementById("searchPulse");
  const resultsContainer = document.getElementById("searchResultsGrid");
  const suggestionsPanel = document.getElementById("suggestionsPanel");
  const recentBlock = document.getElementById("recentSearchesBlock");
  const recentChips = document.getElementById("recentSearchesChips");
  const tabBtns = document.querySelectorAll(".search-tab-btn");

  let searchTimeout = null;
  let currentResults = null;
  let currentTab = "all";

  const ICONS = {
    experience: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 5px; vertical-align: middle;"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>`,
    idea: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 5px; vertical-align: middle;"><polygon points="1 6 1 22 8 18 16 22 23 18 23 2 16 6 8 2 1 6"></polygon><line x1="8" y1="2" x2="8" y2="18"></line><line x1="16" y1="6" x2="16" y2="22"></line></svg>`,
    deal: `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 5px; vertical-align: middle;"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"></path><line x1="7" y1="7" x2="7.01" y2="7"></line></svg>`,
  };

  // --- State Management ---
  function getRecent() {
    return JSON.parse(localStorage.getItem("nepal_recent_searches") || "[]");
  }

  function saveRecent(query) {
    let recent = getRecent();
    recent = [query, ...recent.filter((q) => q !== query)].slice(0, 5);
    localStorage.setItem("nepal_recent_searches", JSON.stringify(recent));
    updateRecentUI();
  }

  function updateRecentUI() {
    const recent = getRecent();
    if (recent.length > 0) {
      recentBlock.style.display = "block";
      recentChips.innerHTML = recent
        .map((q) => `<span class="suggestion-chip">${q}</span>`)
        .join("");

      recentChips.querySelectorAll(".suggestion-chip").forEach((chip) => {
        chip.addEventListener("click", () => {
          searchInput.value = chip.textContent;
          performSearch(chip.textContent);
        });
      });
    } else {
      recentBlock.style.display = "none";
    }
  }

  // --- Overlay Control ---
  function openOverlay() {
    searchOverlay.classList.add("active");
    document.body.style.overflow = "hidden";
    updateRecentUI();
    suggestionsPanel.style.display = "block";

    // Update URL to persist state across refreshes
    if (window.location.hash !== "#search-portal") {
      history.pushState(null, null, "#search-portal");
    }

    setTimeout(() => searchInput.focus(), 500);
  }

  function closeOverlay() {
    searchOverlay.classList.remove("active");
    document.body.style.overflow = "";
    searchInput.value = "";
    resultsContainer.innerHTML = "";
    suggestionsPanel.style.display = "none";
    clearInputBtn.style.display = "none";
    resetTabs();

    // Remove hash from URL
    if (window.location.hash === "#search-portal") {
      history.pushState(
        null,
        null,
        window.location.pathname + window.location.search,
      );
    }
  }

  if (searchTrigger)
    searchTrigger.addEventListener("click", (e) => {
      e.preventDefault();
      openOverlay();
    });
  if (searchClose) searchClose.addEventListener("click", closeOverlay);

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && searchOverlay.classList.contains("active"))
      closeOverlay();
  });

  // --- Search Input Logic ---
  searchInput.addEventListener("keydown", function (e) {
    if (e.key === "Enter") {
      e.preventDefault(); // Prevent accidental form submission/reload
      const query = this.value.trim();
      if (query.length >= 2) performSearch(query);
    }
  });

  searchInput.addEventListener("input", function () {
    const query = this.value.trim();
    clearInputBtn.style.display = query ? "block" : "none";

    if (query.length === 0) {
      suggestionsPanel.style.display = "block";
      resultsContainer.innerHTML = "";
      resetTabs();
      return;
    }

    suggestionsPanel.style.display = "none";

    clearTimeout(searchTimeout);
    if (query.length < 2) return;

    pulse.classList.add("active");
    searchTimeout = setTimeout(() => performSearch(query), 400);
  });

  clearInputBtn.addEventListener("click", () => {
    searchInput.value = "";
    searchInput.focus();
    clearInputBtn.style.display = "none";
    suggestionsPanel.style.display = "block";
    resultsContainer.innerHTML = "";
    resetTabs();
  });

  // Trending Chips
  document.querySelectorAll(".suggestion-chip").forEach((chip) => {
    chip.addEventListener("click", () => {
      searchInput.value = chip.textContent;
      clearInputBtn.style.display = "block";
      performSearch(chip.textContent);
    });
  });

  // Forced Style Redundancy (Mental safety against persistent white-on-white)
  searchInput.addEventListener("focus", function () {
    this.style.backgroundColor = "#0c2136";
    this.style.color = "#ffffff";
  });

  // --- API & Tabs ---
  async function performSearch(query) {
    suggestionsPanel.style.display = "none";
    pulse.classList.add("active");

    try {
      const res = await fetch(
        `../config/global_search.php?q=${encodeURIComponent(query)}`,
      );
      const data = await res.json();

      pulse.classList.remove("active");
      if (data.success) {
        currentResults = data.results;
        saveRecent(query);
        updateTabCounts(data.counts);
        renderResults();
      }
    } catch (err) {
      console.error(err);
      pulse.classList.remove("active");
    }
  }

  function updateTabCounts(counts) {
    document.getElementById("count-all").textContent = counts.total;
    document.getElementById("count-experiences").textContent =
      counts.experiences;
    document.getElementById("count-ideas").textContent = counts.ideas;
    document.getElementById("count-deals").textContent = counts.deals;
  }

  function resetTabs() {
    tabBtns.forEach((b) => b.classList.remove("active"));
    tabBtns[0].classList.add("active");
    currentTab = "all";
    ["all", "experiences", "ideas", "deals"].forEach((id) => {
      const el = document.getElementById(`count-${id}`);
      if (el) el.textContent = "0";
    });
  }

  tabBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      tabBtns.forEach((b) => b.classList.remove("active"));
      btn.classList.add("active");
      currentTab = btn.dataset.tab;
      renderResults();
    });
  });

  function renderResults() {
    if (!currentResults) return;

    let html = "";
    const res = currentResults;
    let globalIndex = 0;

    // Determine what to show based on tab
    const showExp = currentTab === "all" || currentTab === "experiences";
    const showIdeas = currentTab === "all" || currentTab === "ideas";
    const showDeals = currentTab === "all" || currentTab === "deals";

    if (showExp && res.experiences.length > 0) {
      html += generateCategory(
        "COMMUNITY STORIES",
        res.experiences,
        (p) => ({
          href: `experience.php?post=${p.id}`,
          img: p.image_path,
          title: `${ICONS.experience} @${p.username} in ${p.destination || "Nepal"}`,
          meta: p.caption.substring(0, 60) + "...",
        }),
        globalIndex,
      );
      globalIndex += res.experiences.length;
    }

    if (showIdeas && res.ideas.length > 0) {
      html += generateCategory(
        "TRAVEL IDEAS",
        res.ideas,
        (i) => ({
          href: `travel-idea-detail.php?id=${i.id}`,
          img: i.image,
          title: `${ICONS.idea} ${i.title}`,
          meta: i.province,
        }),
        globalIndex,
      );
      globalIndex += res.ideas.length;
    }

    if (showDeals && res.deals.length > 0) {
      html += generateCategory(
        "HOT DEALS",
        res.deals,
        (d) => ({
          href: `deal.php?id=${d.id}`,
          img: d.image,
          title: `${ICONS.deal} ${d.title}`,
          meta: `${d.price} • ${d.region}`,
        }),
        globalIndex,
      );
      globalIndex += res.deals.length;
    }

    if (html === "") {
      html = `<div class="no-results-msg" style="animation: fadeIn 0.5s ease both;">No matches found in this category for "${searchInput.value}".</div>`;
    }

    resultsContainer.innerHTML = html;
  }

  function generateCategory(title, items, mapFn, startIndex) {
    return `
            <div class="result-category">
                <div class="result-category-title">${title}</div>
                <div class="category-items-grid">
                    ${items
                      .map((item, i) => {
                        const data = mapFn(item);
                        return `
                            <a href="${data.href}" class="search-result-card" style="animation-delay: ${(startIndex + i) * 0.05}s">
                                <img src="${data.img}" class="result-img" alt="Result">
                                <div class="result-info">
                                    <span class="result-title">${data.title}</span>
                                    <span class="result-meta">${data.meta}</span>
                                </div>
                            </a>
                        `;
                      })
                      .join("")}
                </div>
            </div>
        `;
  }
  // Check for hash on load
  if (window.location.hash === "#search-portal") {
    openOverlay();
  }

  // Handle back button / forward button portal toggle
  window.addEventListener("hashchange", () => {
    if (window.location.hash === "#search-portal") {
      if (!searchOverlay.classList.contains("active")) openOverlay();
    } else {
      if (searchOverlay.classList.contains("active")) closeOverlay();
    }
  });
});
