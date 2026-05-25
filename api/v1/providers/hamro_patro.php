<?php
/**
 
 * Author: Bijay Sapkota
 * Group: L5CG6
 */
class HamroPatroProvider {
    private $url = "https://www.hamropatro.com/calendar";

    public function fetchUpcomingFestivals() {
        $html = $this->getHtml($this->url);
        if (!$html) return [];

        // Focus on the "Upcoming Events" section which usually lists major festivals
        // We look for patterns like <li> ... <a href="/date/...">TITLE</a> ... </li>
        // Note: Hamro Patro uses a lot of dynamic classes, so we rely on link structure.
        
        $festivals = [];
        
        // Regex to find dates and titles in the actual HTML
        // Patterns like: <a href="/date/2083-1-1">Festival Title</a>
        $pattern = '/href="\/date\/([0-9\-]+)"[^>]*>([^<]+)<\/a>/u';
        
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $fullDate = $match[1]; // e.g. 2083-1-1
                $title = $this->cleanupTitle(strip_tags($match[2]));
                
                // Exclude generic things, empty strings, and 'VIEW DETAILS +'
                if (empty($title)) continue;
                if (strpos($title, 'आज') !== false || strpos($title, 'भोलि') !== false) continue;
                if (strtoupper($title) === 'VIEW DETAILS +') continue;
                if (strpos(strtoupper($title), 'SEE 208') !== false) continue;
                
                $dateParts = explode('-', $fullDate);
                if (count($dateParts) < 2) continue;
                
                $bsMonth = (int)$dateParts[1];
                
                $festivals[] = [
                    'id' => 'hp-' . md5($title . $fullDate),
                    'title' => $this->cleanupTitle($title),
                    'description' => "Official festival listed on Hamro Patro. Celebrated throughout Nepal with traditional rituals and community gatherings.",
                    'image_path' => 'images/bhaktapur_temple.png', // Default cultural image
                    'category' => 'FESTIVAL',
                    'location' => 'National',
                    'event_date' => $this->getBSDateString($dateParts),
                    'month' => $this->mapBSMonthToAD($bsMonth),
                    'source' => 'hamro_patro',
                    'is_featured' => 0
                ];
            }
        }

        return $festivals;
    }

    private function getHtml($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    private function cleanupTitle($title) {
        // Remove string artifacts and compress whitespace
        $title = current(explode('/', $title));
        $title = trim($title);
        $title = preg_replace('/\s+/', ' ', $title);
        return $title;
    }

    private function mapBSMonthToAD($bsMonth) {
        // Approximate mapping for Nepal Travel filtering
        // BS 1 (Baishakh) starts in mid-APR
        $months = [
            1 => 'APR', 2 => 'MAY', 3 => 'JUN', 4 => 'JUL', 
            5 => 'AUG', 6 => 'SEP', 7 => 'OCT', 8 => 'NOV', 
            9 => 'DEC', 10 => 'JAN', 11 => 'FEB', 12 => 'MAR'
        ];
        return $months[$bsMonth] ?? 'JAN';
    }

    private function getBSDateString($parts) {
        // Convert Y-M-D to a readable "15 Baisakh" style if possible
        $bsMonths = [1=>'Baisakh', 2=>'Jestha', 3=>'Ashadh', 4=>'Shrawan', 5=>'Bhadra', 6=>'Ashwin', 7=>'Kartik', 8=>'Mangsir', 9=>'Poush', 10=>'Magh', 11=>'Falgun', 12=>'Chaitra'];
        return $parts[2] . " " . ($bsMonths[(int)$parts[1]] ?? '');
    }
}
?>