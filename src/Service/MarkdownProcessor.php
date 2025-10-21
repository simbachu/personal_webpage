<?php

declare(strict_types=1);

namespace App\Service;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\Footnote\FootnoteExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;

//! @brief Service for processing markdown content
final class MarkdownProcessor
{
    //! @brief Process markdown content and return parsed data
    //! @param markdownContent The markdown content to process
    //! @retval array{title: string, author: string|null, date: string|null, content: string, footnotes: array} Parsed data
    public function process(string $markdownContent): array
    {
        $lines = explode("\n", $markdownContent);
        $title = null;
        $author = null;
        $date = null;
        $contentStart = 0;
        $hasMetadata = false;

        // Find title (first # heading)
        foreach ($lines as $i => $line) {
            if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                $title = trim($matches[1]);
                $contentStart = $i + 1;
                break;
            }
        }

        // Find metadata (By: and On: lines)
        for ($i = $contentStart; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (preg_match('/^By:\s*(.+)$/', $line, $matches)) {
                $author = trim($matches[1]);
                $hasMetadata = true;
                $contentStart = $i + 1;
            } elseif (preg_match('/^On:\s*(.+)$/', $line, $matches)) {
                $date = trim($matches[1]);
                $hasMetadata = true;
                $contentStart = $i + 1;
            } elseif ($line !== '' && !preg_match('/^[#\s]/', $line)) {
                // Stop at first non-empty, non-metadata line
                $contentStart = $i;
                break;
            }
        }

        if (!$hasMetadata && $contentStart === 0) {
            // If no metadata, include the title in content by starting from the beginning
            $contentStart = 0;
        }

        $contentLines = array_slice($lines, $contentStart);
        $markdownContent = implode("\n", $contentLines);

        // Configure CommonMark with extensions
        $config = [
            'footnote' => [
                'backref_class' => 'footnote-backref',
                'backref_symbol' => '↩',
                'container_add_hr' => true,
                'container_class' => 'footnotes',
                'ref_class' => 'footnote-ref',
                'ref_id_prefix' => 'fnref:',
                'footnote_class' => 'footnote',
                'footnote_id_prefix' => 'fn:',
            ]
        ];

        $environment = new Environment($config);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new FootnoteExtension());

        $converter = new CommonMarkConverter($config, $environment);
        $htmlContent = $converter->convert($markdownContent);

        // Post-process the HTML to adjust heading levels and add superscript support
        $processedContent = $this->postProcessHtml($htmlContent->getContent());

        // Extract footnotes from the processed content
        $footnotes = $this->extractFootnotes($processedContent);
        $contentWithoutFootnotes = $this->removeFootnotesSection($processedContent);

        return [
            'title' => $title,
            'author' => $author,
            'date' => $date,
            'content' => $contentWithoutFootnotes,
            'footnotes' => $footnotes
        ];
    }

    //! @brief Post-process HTML to adjust heading levels and add superscript support
    //! @param html The HTML content to process
    //! @retval string Processed HTML content
    private function postProcessHtml(string $html): string
    {
        // Adjust heading levels (h1 -> h2, h2 -> h3, etc.)
        $html = preg_replace_callback('/<h([1-6])>/', function($matches) {
            $level = (int)$matches[1];
            $newLevel = min($level + 1, 6); // Don't go beyond h6
            return "<h{$newLevel}>";
        }, $html);

        $html = preg_replace_callback('/<\/h([1-6])>/', function($matches) {
            $level = (int)$matches[1];
            $newLevel = min($level + 1, 6); // Don't go beyond h6
            return "</h{$newLevel}>";
        }, $html);

        // Handle footnotes manually since the extension isn't working (do this before superscript)
        $html = $this->processFootnotes($html);

        // Convert ^<a href="url">text</a>^ to <sup><a href="url">text</a></sup> (superscripted hyperlinks)
        $html = preg_replace('/\^<a href="([^"]+)">([^<]+)<\/a>\^/', '<sup><a href="$1">$2</a></sup>', $html);

        // Convert ^text^ to <sup>text</sup> (but not if it's already processed as a footnote or hyperlink)
        $html = preg_replace('/\^([^<>\n\[\]]+?)\^/', '<sup>$1</sup>', $html);

        return $html;
    }

    //! @brief Process footnotes manually since the CommonMark extension isn't working
    //! @param html The HTML content to process
    //! @retval string HTML with processed footnotes
    private function processFootnotes(string $html): string
    {
        // Find all footnote definitions [^id]: text
        $footnotes = [];

        // Extract all footnote definitions from paragraphs
        $html = preg_replace_callback('/<p>\[(\^[^\]]+)\]:\s*(.+?)<\/p>/s', function($matches) use (&$footnotes) {
            $firstId = $matches[1];
            $fullText = $matches[2];

            // The first footnote starts immediately after the colon
            $currentFootnote = $firstId;
            $currentText = '';

            // Split by lines and process each footnote definition
            $lines = explode("\n", $fullText);

            foreach ($lines as $line) {
                $line = trim($line);
                if (preg_match('/^\[(\^[^\]]+)\]:\s*(.*)$/', $line, $lineMatches)) {
                    // Save previous footnote if exists
                    if ($currentFootnote !== null) {
                        $footnotes[$currentFootnote] = trim($currentText);
                    }

                    // Start new footnote
                    $currentFootnote = $lineMatches[1];
                    $currentText = $lineMatches[2];
                } else {
                    // Continue current footnote text
                    if ($currentFootnote !== null && $line !== '') {
                        $currentText .= ($currentText ? "\n" : '') . $line;
                    }
                }
            }

            // Save last footnote
            if ($currentFootnote !== null) {
                $footnotes[$currentFootnote] = trim($currentText);
            }

            return ''; // Remove the paragraph
        }, $html);

        // Replace footnote references [^id] with links
        $footnoteCounter = 1;
        $processedFootnotes = [];

        $html = preg_replace_callback('/\[(\^[^\]]+)\]/', function($matches) use (&$footnotes, &$footnoteCounter, &$processedFootnotes) {
            $id = $matches[1];
            if (isset($footnotes[$id])) {
                $counter = $footnoteCounter++;
                $processedFootnotes[$counter] = $footnotes[$id];
                return '<sup><a href="#fn:' . $counter . '" id="fnref:' . $counter . '" class="footnote-ref">' . $counter . '</a></sup>';
            }
            return $matches[0]; // Return original if not found
        }, $html);

        // Add footnotes section if we have any
        if (!empty($processedFootnotes)) {
            $footnotesHtml = '<hr><section class="footnotes">';
            foreach ($processedFootnotes as $num => $text) {
                $footnotesHtml .= '<div class="footnote" id="fn:' . $num . '">';
                $footnotesHtml .= '<p>' . $text . ' <a href="#fnref:' . $num . '" class="footnote-backref">↩</a></p>';
                $footnotesHtml .= '</div>';
            }
            $footnotesHtml .= '</section>';
            $html .= $footnotesHtml;
        }

        return $html;
    }

    //! @brief Extract footnotes from processed HTML content
    //! @param html The HTML content with footnotes
    //! @retval array Array of footnotes with id => content pairs
    private function extractFootnotes(string $html): array
    {
        $footnotes = [];

        // Find the footnotes section
        if (preg_match('/<hr><section class="footnotes">(.*?)<\/section>/s', $html, $matches)) {
            $footnotesSection = $matches[1];

            // Extract individual footnotes
            preg_match_all('/<div class="footnote" id="fn:(\d+)"><p>(.*?)<\/p><\/div>/s', $footnotesSection, $footnoteMatches, PREG_SET_ORDER);

            foreach ($footnoteMatches as $match) {
                $id = (int)$match[1];
                $content = $match[2];

                // Remove the back-reference link from the content
                $content = preg_replace('/ <a href="#fnref:\d+" class="footnote-backref">↩<\/a>$/', '', $content);

                $footnotes[$id] = trim($content);
            }
        }

        return $footnotes;
    }

    //! @brief Remove the footnotes section from HTML content
    //! @param html The HTML content with footnotes section
    //! @retval string HTML content without footnotes section
    private function removeFootnotesSection(string $html): string
    {
        // Remove the entire footnotes section
        return preg_replace('/<hr><section class="footnotes">.*?<\/section>/s', '', $html);
    }
}
