<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Space;
use App\Entity\Mark;

class BookmarkExportService
{
    /**
     * Génère un fichier HTML au format Netscape Bookmark compatible avec Chrome
     * Chaque Space devient un dossier, chaque Mark devient un favori
     */
    public function generateBookmarkFile(User $user): string
    {
        $timestamp = time();
        
        $html = <<<HTML
<!DOCTYPE NETSCAPE-Bookmark-file-1>
<!-- This is an automatically generated file.
     It will be read and overwritten.
     DO NOT EDIT! -->
<META HTTP-EQUIV="Content-Type" CONTENT="text/html; charset=UTF-8">
<TITLE>Bookmarks</TITLE>
<H1>Bookmarks</H1>
<DL><p>
    <DT><H3 ADD_DATE="{$timestamp}" LAST_MODIFIED="{$timestamp}">Favospace - {$this->escapeHtml($user->getName())}</H3>
    <DL><p>

HTML;

        // Parcourir tous les espaces de l'utilisateur
        foreach ($user->getSpaces() as $space) {
            $html .= $this->generateSpaceFolder($space, $timestamp);
        }

        $html .= <<<HTML
    </DL><p>
</DL><p>

HTML;

        return $html;
    }

    /**
     * Génère le HTML pour un dossier (Space) et ses favoris (Marks)
     */
    private function generateSpaceFolder(Space $space, int $timestamp): string
    {
        $spaceName = $this->escapeHtml($space->getName());
        $html = "        <DT><H3 ADD_DATE=\"{$timestamp}\" LAST_MODIFIED=\"{$timestamp}\">{$spaceName}</H3>\n";
        $html .= "        <DL><p>\n";

        // Ajouter tous les marks de cet espace
        foreach ($space->getMarks() as $mark) {
            $html .= $this->generateBookmark($mark, $timestamp);
        }

        $html .= "        </DL><p>\n";

        return $html;
    }

    /**
     * Génère le HTML pour un favori (Mark)
     */
    private function generateBookmark(Mark $mark, int $timestamp): string
    {
        $name = $this->escapeHtml($mark->getName());
        $url = $this->escapeHtml($mark->getUrl());
        $description = $mark->getDescription() ? $this->escapeHtml($mark->getDescription()) : '';

        // Chrome utilise l'attribut ICON pour stocker le favicon (optionnel)
        $html = "            <DT><A HREF=\"{$url}\" ADD_DATE=\"{$timestamp}\"";
        
        if ($description) {
            $html .= " DESCRIPTION=\"{$description}\"";
        }
        
        $html .= ">{$name}</A>\n";

        return $html;
    }

    /**
     * Échappe les caractères HTML spéciaux
     */
    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}
