<?php
/**
 * Fonctions utilitaires partagées.
 */

function format_fcfa(float $montant): string
{
    return number_format($montant, 0, ',', ' ') . ' ' . DEVISE;
}

function format_date(string $datetime): string
{
    $ts = strtotime($datetime);
    return $ts ? date('d/m/Y H:i', $ts) : '';
}

function generate_reference(): string
{
    return 'CMD-' . date('ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
}

function statut_badge_class(string $statut): string
{
    return match ($statut) {
        'en_attente'   => 'badge-warning',
        'validee'      => 'badge-info',
        'en_livraison' => 'badge-info',
        'en_cours'     => 'badge-info',
        'livree'       => 'badge-success',
        'paye'         => 'badge-success',
        'partiel'      => 'badge-warning',
        'annulee'      => 'badge-danger',
        'echouee'      => 'badge-danger',
        default        => 'badge-neutral',
    };
}

function statut_label(string $statut): string
{
    return match ($statut) {
        'en_attente'   => 'En attente',
        'validee'      => 'Validée',
        'en_livraison' => 'En livraison',
        'en_cours'     => 'En cours',
        'livree'       => 'Livrée',
        'paye'         => 'Payé',
        'partiel'      => 'Partiel',
        'annulee'      => 'Annulée',
        'echouee'      => 'Échouée',
        default        => ucfirst($statut),
    };
}
