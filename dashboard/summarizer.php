<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $text = $_POST['text'];
    
    // Function to preprocess and extract keywords
    function extractKeywords($text) {
        // Convert to lowercase and remove special characters
        $text = strtolower(preg_replace('/[^a-zA-Z0-9\s]/', '', $text));
        
        // Common stop words to filter out
        $stopWords = array(
            'a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 
            'from', 'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 
            'that', 'the', 'to', 'was', 'were', 'will', 'with'
        );
        
        // Split text into words
        $words = explode(' ', trim($text));
        
        // Count word frequency
        $wordFreq = array();
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) > 2 && !in_array($word, $stopWords)) {
                $wordFreq[$word] = isset($wordFreq[$word]) ? $wordFreq[$word] + 1 : 1;
            }
        }
        
        // Sort by frequency
        arsort($wordFreq);
        
        // Get top keywords (intelligent scoring)
        $keywords = array();
        $totalWords = count($words);
        
        foreach ($wordFreq as $word => $freq) {
            // Score based on frequency and word length
            $score = $freq * (strlen($word) / 5);
            // Boost score for less common words (inverse document frequency approximation)
            $score *= log($totalWords / ($freq + 1));
            $keywords[$word] = $score;
        }
        
        // Sort by score and take top 5-10 keywords
        arsort($keywords);
        $topKeywords = array_slice(array_keys($keywords), 0, min(10, count($keywords)));
        
        return implode(', ', $topKeywords);
    }
    
    try {
        $summary = extractKeywords($text);
        
        if (empty($summary)) {
            throw new Exception("Unable to extract meaningful keywords from the text.");
        }
        
        echo "<h3>Summary Keywords:</h3>";
        echo "<p>" . htmlspecialchars($summary) . "</p>";
        echo "<a href='index.html'>Go Back</a>";
        
    } catch (Exception $e) {
        echo "<h3>Error:</h3>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='index.html'>Go Back</a>";
    }
}
?>