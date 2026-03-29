<?php
declare(strict_types=1);

namespace App\DataFixtures;

/**
 * Documentation-only placeholder — not loaded as a Doctrine fixture yet.
 *
 * Seed sample events with: php bin/console doctrine:fixtures:load
 * Or run the SQL below in psql / pgAdmin:
 *
 * INSERT INTO events (title, description, date, location, seats, created_at) VALUES
 * ('Conférence IA & Machine Learning 2026',
 *  'Une journée complète dédiée à l''intelligence artificielle et au machine learning. Des experts du domaine partageront leurs dernières recherches et applications pratiques dans divers secteurs.',
 *  '2026-04-15 09:00:00', 'Amphithéâtre A — ISSAT Sousse', 120, NOW()),
 * ('Hackathon Web & Mobile',
 *  '48 heures pour concevoir et développer une application innovante. Travaillez en équipes sur des défis réels posés par des entreprises partenaires. Prix à gagner !',
 *  '2026-04-22 08:00:00', 'Salle informatique 3 — ISSAT Sousse', 60, NOW()),
 * ('Workshop Symfony 7 & API Platform',
 *  'Atelier pratique sur Symfony 7 et API Platform. Construisez une API REST complète avec authentification JWT, documentation OpenAPI et bonnes pratiques.',
 *  '2026-05-05 14:00:00', 'Labo Dev — ISSAT Sousse', 30, NOW()),
 * ('Journée Portes Ouvertes FIA3',
 *  'Présentation des projets de fin d''études des étudiants FIA3. Démonstrations, présentations et networking avec les entreprises partenaires.',
 *  '2026-05-20 10:00:00', 'Hall principal — ISSAT Sousse', 200, NOW()),
 * ('Séminaire Cybersécurité',
 *  'Les menaces actuelles en cybersécurité et comment s''en protéger. Présentation des nouvelles vulnérabilités, techniques de pentesting et solutions de protection.',
 *  '2026-06-10 09:30:00', 'Salle de conférence B — ISSAT Sousse', 80, NOW());
 */
final class AppFixtures
{
    private function __construct()
    {
    }
}
