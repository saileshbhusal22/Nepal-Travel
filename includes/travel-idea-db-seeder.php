<?php
require_once __DIR__ . '/travel-ideas-data.php';
require_once __DIR__ . '/travel-idea-details-data.php';

function travelIdeaDbNormalizeSlug($text) {
    $slug = strtolower(trim($text));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

function travelIdeaDbParseDuration($durationStr) {
    $durationStr = trim($durationStr ?? '');
    $days = 0;
    $nights = 0;
    if (preg_match('/(\d+)\s*D/i', $durationStr, $matches)) {
        $days = (int) $matches[1];
    }
    if (preg_match('/(\d+)\s*N/i', $durationStr, $matches)) {
        $nights = (int) $matches[1];
    }
    return [$days, $nights];
}

function travelIdeaDbGetProvinceId($conn, $provinceName, $provinceSlug) {
    if (empty($provinceSlug)) {
        $provinceSlug = travelIdeaDbNormalizeSlug($provinceName);
    }
    $stmt = $conn->prepare("SELECT id FROM provinces WHERE slug = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $provinceSlug);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $id = (int) $row['id'];
            $stmt->close();
            return $id;
        }
        $stmt->close();
    }

    $nameToInsert = !empty($provinceName) ? preg_replace('/\s*Province$/i', '', trim($provinceName)) : $provinceSlug;
    if (empty($nameToInsert)) {
        return null;
    }
    $stmt = $conn->prepare("INSERT INTO provinces (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    if ($stmt) {
        $stmt->bind_param('ss', $nameToInsert, $provinceSlug);
        $stmt->execute();
        $id = $stmt->insert_id;
        if ($id === 0) {
            // row existed, fetch it again
            $stmt->close();
            return travelIdeaDbGetProvinceId($conn, $provinceName, $provinceSlug);
        }
        $stmt->close();
        return $id;
    }
    return null;
}

function travelIdeaDbGetExperienceTypeId($conn, $typeName) {
    $typeName = trim($typeName);
    if ($typeName === '') {
        return null;
    }
    $slug = travelIdeaDbNormalizeSlug($typeName);
    $stmt = $conn->prepare("SELECT id FROM experience_types WHERE slug = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $slug);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $row = $res->fetch_assoc()) {
            $id = (int) $row['id'];
            $stmt->close();
            return $id;
        }
        $stmt->close();
    }

    $stmt = $conn->prepare("INSERT INTO experience_types (name, slug) VALUES (?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name)");
    if ($stmt) {
        $stmt->bind_param('ss', $typeName, $slug);
        $stmt->execute();
        $id = $stmt->insert_id;
        if ($id === 0) {
            $stmt->close();
            return travelIdeaDbGetExperienceTypeId($conn, $typeName);
        }
        $stmt->close();
        return $id;
    }
    return null;
}

function travelIdeaDbEnsureExperienceTypeIds($conn, $typeValue) {
    $typeIds = [];
    if (is_array($typeValue)) {
        $typeNames = $typeValue;
    } else {
        $typeNames = array_filter(array_map('trim', explode(',', (string) $typeValue)));
    }
    foreach ($typeNames as $typeName) {
        if ($typeName === '') {
            continue;
        }
        $typeId = travelIdeaDbGetExperienceTypeId($conn, $typeName);
        if ($typeId) {
            $typeIds[] = $typeId;
        }
    }
    return array_unique($typeIds);
}

function travelIdeaDbGetTravelIdeaIdBySlug($conn, $slug) {
    $stmt = $conn->prepare("SELECT id FROM travel_ideas WHERE slug = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $slug);
    $stmt->execute();
    $result = $stmt->get_result();
    $id = null;
    if ($result && $row = $result->fetch_assoc()) {
        $id = (int) $row['id'];
    }
    $stmt->close();
    return $id;
}

function travelIdeaDbHasDetails($conn, $ideaId) {
    $stmt = $conn->prepare("SELECT id FROM travel_idea_details WHERE idea_id = ? LIMIT 1");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $ideaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $has = $result && $result->fetch_assoc() ? true : false;
    $stmt->close();
    return $has;
}

function travelIdeaDbHasItineraries($conn, $ideaId) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS count FROM itineraries WHERE idea_id = ?");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param('i', $ideaId);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = 0;
    if ($result && $row = $result->fetch_assoc()) {
        $count = (int) $row['count'];
    }
    $stmt->close();
    return $count > 0;
}

function travelIdeaDbSeedTravelIdeaBySlug($conn, $slug) {
    global $travel_ideas, $travel_idea_details;
    $slug = trim($slug);
    if ($slug === '') {
        return false;
    }

    $staticIdea = null;
    foreach ($travel_ideas as $idea) {
        $ideaSlug = $idea['slug'] ?? $idea['id'] ?? null;
        if ($ideaSlug === $slug) {
            $staticIdea = $idea;
            break;
        }
    }
    if (!$staticIdea) {
        return false;
    }

    $ideaId = travelIdeaDbGetTravelIdeaIdBySlug($conn, $slug);
    $provinceName = $staticIdea['province'] ?? '';
    $provinceSlug = $staticIdea['province_slug'] ?? travelIdeaDbNormalizeSlug($provinceName);
    $provinceId = travelIdeaDbGetProvinceId($conn, $provinceName, $provinceSlug);
    $subtitle = $staticIdea['description'] ?? null;
    $durationParts = travelIdeaDbParseDuration($staticIdea['duration'] ?? '');
    $imagePath = $staticIdea['image'] ?? null;
    $difficulty = isset($staticIdea['difficulty']) ? trim($staticIdea['difficulty']) : null;
    $transport = null;
    $accommodation = null;
    $bestTime = null;
    $proTip = null;
    $title = $staticIdea['title'] ?? ($staticIdea['slug'] ?? $slug);
    $slugValue = $slug;

    if (!$ideaId) {
        $stmt = $conn->prepare("INSERT INTO travel_ideas (user_id, title, subtitle, slug, province_id, province_slug, image_path, duration_days, nights, transport, accommodation, best_time, pro_tip, difficulty) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return false;
        }
        $stmt->bind_param('sssiisiisssss', $title, $subtitle, $slugValue, $provinceId, $provinceSlug, $imagePath, $durationParts[0], $durationParts[1], $transport, $accommodation, $bestTime, $proTip, $difficulty);
        $stmt->execute();
        if ($stmt->affected_rows > 0) {
            $ideaId = $stmt->insert_id;
        }
        $stmt->close();
        if (!$ideaId) {
            return false;
        }
    }

    $staticDetails = $travel_idea_details[$slug] ?? null;
    if ($staticDetails && !travelIdeaDbHasDetails($conn, $ideaId)) {
        $highlightsJson = json_encode(array_values($staticDetails['highlights'] ?? []), JSON_UNESCAPED_UNICODE);
        $logisticsJson = json_encode($staticDetails['logistics'] ?? [], JSON_UNESCAPED_UNICODE);
        $heroImage = $staticDetails['hero_image'] ?? $imagePath;
        $content = $staticDetails['intro'] ?? $staticDetails['content'] ?? $subtitle;

        $stmt = $conn->prepare("INSERT INTO travel_idea_details (idea_id, content, highlights, logistics, hero_image) VALUES (?, ?, ?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param('issss', $ideaId, $content, $highlightsJson, $logisticsJson, $heroImage);
            $stmt->execute();
            $stmt->close();
        }
    }

    $experienceTypeIds = travelIdeaDbEnsureExperienceTypeIds($conn, $staticIdea['type'] ?? '');
    if (!empty($experienceTypeIds)) {
        $experienceStmt = $conn->prepare("INSERT IGNORE INTO travel_idea_experiences (idea_id, experience_type_id) VALUES (?, ?)");
        if ($experienceStmt) {
            foreach ($experienceTypeIds as $experienceTypeId) {
                $experienceStmt->bind_param('ii', $ideaId, $experienceTypeId);
                $experienceStmt->execute();
            }
            $experienceStmt->close();
        }
    }

    if ($staticDetails && !travelIdeaDbHasItineraries($conn, $ideaId)) {
        $itineraryStmt = $conn->prepare("INSERT INTO itineraries (idea_id, day_order, day_title, morning, afternoon, evening, image_path) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if ($itineraryStmt) {
            $index = 0;
            foreach ($staticDetails['itinerary'] as $dayKey => $item) {
                $index++;
                $dayOrder = 0;
                if (preg_match('/(\d+)/', (string) $dayKey, $matches)) {
                    $dayOrder = (int) $matches[1];
                }
                if ($dayOrder <= 0) {
                    $dayOrder = $index;
                }
                $dayTitle = $item['title'] ?? $dayKey;
                $morning = $item['morning'] ?? null;
                $afternoon = $item['afternoon'] ?? null;
                $evening = $item['evening'] ?? null;
                $itemImage = $item['img'] ?? null;
                $itineraryStmt->bind_param('iisssss', $ideaId, $dayOrder, $dayTitle, $morning, $afternoon, $evening, $itemImage);
                $itineraryStmt->execute();
            }
            $itineraryStmt->close();
        }
    }

    return true;
}

function travelIdeaDbSeedStaticTravelIdeas($conn) {
    global $travel_ideas;
    $inserted = 0;
    foreach ($travel_ideas as $idea) {
        $slug = $idea['slug'] ?? $idea['id'] ?? null;
        if (!$slug) {
            continue;
        }
        if (travelIdeaDbSeedTravelIdeaBySlug($conn, $slug)) {
            $inserted++;
        }
    }
    return $inserted;
}
