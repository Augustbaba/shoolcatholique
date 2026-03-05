<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Espace Parent — CCPA</title>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
<style>
:root {
  --vert: #1b4d2e; --vert-clair: #2d7a4a;
  --or: #b8953a; --or-clair: #d4aa50;
  --blanc: #ffffff; --creme: #faf8f4; --gris: #f2f0eb;
  --texte: #1a1a1a; --texte-doux: #6b6b6b; --bord: #e0dcd4;
  --rouge: #c0392b; --bleu: #2c5f8a;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DM Sans', sans-serif; background: var(--gris); min-height: 100vh; display: flex; }

/* SIDEBAR */
.sidebar {
  width: 230px; min-height: 100vh;
  background: var(--vert);
  display: flex; flex-direction: column;
  padding: 0;
  position: sticky; top: 0; flex-shrink: 0;
}
.sidebar-brand { padding: 1.5rem 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.08); }
.sidebar-brand strong { display: block; color: var(--blanc); font-family: 'Cormorant Garamond', serif; font-size: 15px; font-weight: 700; }
.sidebar-brand span { color: var(--or-clair); font-size: 10px; letter-spacing: 2px; text-transform: uppercase; }
.sidebar-user { padding: 1.25rem; border-bottom: 1px solid rgba(255,255,255,0.08); }
.sidebar-user-nom { color: var(--blanc); font-size: 13px; font-weight: 600; }
.sidebar-user-role { color: rgba(255,255,255,0.4); font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; margin-top: 2px; }
.sidebar-menu { flex: 1; padding: 1rem 0; }
.menu-groupe { margin-bottom: 1.5rem; }
.menu-groupe-titre { font-size: 9px; letter-spacing: 2.5px; text-transform: uppercase; color: rgba(255,255,255,0.3); padding: 0 1.25rem; margin-bottom: 0.5rem; font-weight: 600; }
.menu-lien {
  display: block; padding: 0.65rem 1.25rem;
  color: rgba(255,255,255,0.6); text-decoration: none;
  font-size: 13px; transition: all 0.2s;
  border-left: 2px solid transparent;
}
.menu-lien:hover { color: var(--blanc); background: rgba(255,255,255,0.05); }
.menu-lien.actif { color: var(--blanc); border-left-color: var(--or); background: rgba(255,255,255,0.06); }
.sidebar-bas { padding: 1.25rem; border-top: 1px solid rgba(255,255,255,0.08); }
.deconnexion { color: rgba(255,255,255,0.4); font-size: 12px; text-decoration: none; display: block; transition: color 0.2s; }
.deconnexion:hover { color: rgba(255,255,255,0.75); }

/* MAIN */
.main { flex: 1; display: flex; flex-direction: column; }
.topbar {
  background: var(--blanc); border-bottom: 1px solid var(--bord);
  padding: 0 2.5rem; height: 58px;
  display: flex; align-items: center; justify-content: space-between;
  position: sticky; top: 0; z-index: 10;
}
.topbar-titre { font-family: 'Cormorant Garamond', serif; font-size: 1.3rem; font-weight: 700; }
.topbar-droite { display: flex; align-items: center; gap: 1.5rem; }
.notif-badge {
  background: var(--rouge); color: var(--blanc);
  font-size: 9px; font-weight: 700;
  padding: 2px 6px; border-radius: 10px;
}
.eleve-switch {
  display: flex; gap: 0.5rem;
}
.eleve-btn {
  padding: 5px 14px; border: 1px solid var(--bord); border-radius: 2px;
  font-family: 'DM Sans', sans-serif; font-size: 12px;
  background: var(--blanc); color: var(--texte-doux);
  cursor: pointer; transition: all 0.2s;
}
.eleve-btn.actif { background: var(--vert); color: var(--blanc); border-color: var(--vert); }

.contenu { padding: 2.5rem; flex: 1; }

/* CARTES KPI */
.kpi-grille { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
.kpi { background: var(--blanc); border: 1px solid var(--bord); padding: 1.5rem; }
.kpi-valeur { font-family: 'Cormorant Garamond', serif; font-size: 2.2rem; font-weight: 700; color: var(--texte); line-height: 1; }
.kpi-valeur.vert { color: var(--vert-clair); }
.kpi-valeur.or { color: var(--or); }
.kpi-valeur.rouge { color: var(--rouge); }
.kpi-label { font-size: 11px; color: var(--texte-doux); letter-spacing: 1px; text-transform: uppercase; margin-top: 0.4rem; }

/* NOTES */
.section-titre { font-family: 'Cormorant Garamond', serif; font-size: 1.35rem; font-weight: 700; margin-bottom: 1rem; color: var(--texte); }
.grille-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
.carte { background: var(--blanc); border: 1px solid var(--bord); }
.carte-entete { padding: 1.1rem 1.5rem; border-bottom: 1px solid var(--bord); display: flex; justify-content: space-between; align-items: center; }
.carte-entete h3 { font-size: 13px; font-weight: 600; }
.carte-entete a { font-size: 11px; color: var(--vert); text-decoration: none; letter-spacing: 0.5px; }

table { width: 100%; border-collapse: collapse; }
th { text-align: left; padding: 0.7rem 1.5rem; font-size: 10px; letter-spacing: 1.5px; text-transform: uppercase; color: var(--texte-doux); font-weight: 600; border-bottom: 1px solid var(--bord); background: var(--creme); }
td { padding: 0.85rem 1.5rem; font-size: 13px; border-bottom: 1px solid var(--bord); }
tr:last-child td { border-bottom: none; }
.note-badge {
  display: inline-block; padding: 2px 10px; font-size: 12px; font-weight: 600;
  border-radius: 2px;
}
.note-badge.tb { background: #e8f5e9; color: #2d7a4a; }
.note-badge.b { background: #e3f0ff; color: var(--bleu); }
.note-badge.ab { background: #fff8e1; color: #b8953a; }
.note-badge.p { background: #fdecea; color: var(--rouge); }
.moy-generale { padding: 1.25rem 1.5rem; display: flex; justify-content: space-between; align-items: center; background: var(--vert); }
.moy-lbl { color: rgba(255,255,255,0.7); font-size: 12px; letter-spacing: 1px; text-transform: uppercase; font-weight: 600; }
.moy-val { font-family: 'Cormorant Garamond', serif; font-size: 2rem; font-weight: 700; color: var(--or-clair); }

/* ABSENCES */
.absence-liste { padding: 0.5rem 0; }
.absence-item { display: flex; align-items: center; justify-content: space-between; padding: 0.85rem 1.5rem; border-bottom: 1px solid var(--bord); font-size: 13px; }
.absence-item:last-child { border-bottom: none; }
.absence-date { color: var(--texte-doux); font-size: 11px; }
.absence-statut { font-size: 10px; padding: 2px 8px; letter-spacing: 1px; text-transform: uppercase; font-weight: 600; }
.abs-justif { color: var(--vert-clair); border: 1px solid var(--vert-clair); }
.abs-injust { color: var(--rouge); border: 1px solid var(--rouge); }

/* MESSAGES */
.msg-liste { }
.msg-item { display: flex; gap: 1rem; padding: 1rem 1.5rem; border-bottom: 1px solid var(--bord); align-items: flex-start; }
.msg-item:last-child { border-bottom: none; }
.msg-initiale { width: 34px; height: 34px; border-radius: 50%; background: var(--vert); color: var(--blanc); display: flex; align-items: center; justify-content: center; font-size: 13px; font-weight: 700; flex-shrink: 0; }
.msg-nom { font-size: 13px; font-weight: 600; margin-bottom: 2px; }
.msg-apercu { font-size: 12px; color: var(--texte-doux); }
.msg-date { font-size: 10px; color: var(--texte-doux); white-space: nowrap; }
.msg-non-lu .msg-nom::after { content: ''; display: inline-block; width: 7px; height: 7px; border-radius: 50%; background: var(--or); margin-left: 8px; vertical-align: middle; }
</style>
</head>
<body>

<aside class="sidebar">
  <div class="sidebar-brand">
    <strong>Père Aupiais</strong>
    <span>Espace Famille</span>
  </div>
  <div class="sidebar-user">
    <div class="sidebar-user-nom">M. ADJOBI Kofi</div>
    <div class="sidebar-user-role">Parent · 2 enfants</div>
  </div>
  <nav class="sidebar-menu">
    <div class="menu-groupe">
      <div class="menu-groupe-titre">Scolarité</div>
      <a href="dashboard-parent.html" class="menu-lien actif">Tableau de bord</a>
      <a href="#" class="menu-lien">Notes & évaluations</a>
      <a href="#" class="menu-lien">Bulletins de notes</a>
      <a href="#" class="menu-lien">Absences & retards</a>
    </div>
    <div class="menu-groupe">
      <div class="menu-groupe-titre">Communication</div>
      <a href="#" class="menu-lien">Messagerie</a>
      <a href="#" class="menu-lien">Calendrier scolaire</a>
      <a href="#" class="menu-lien">Actualités CCPA</a>
    </div>
    <div class="menu-groupe">
      <div class="menu-groupe-titre">Compte</div>
      <a href="#" class="menu-lien">Mon profil</a>
      <a href="#" class="menu-lien">Paramètres</a>
    </div>
  </nav>
  <div class="sidebar-bas">
    <a href="connexion.html" class="deconnexion">Se déconnecter</a>
  </div>
</aside>

<div class="main">
  <div class="topbar">
    <div class="topbar-titre">Tableau de bord</div>
    <div class="topbar-droite">
      <div class="eleve-switch">
        <button class="eleve-btn actif">Kossivi · Tle D</button>
        <button class="eleve-btn">Ama · 3e</button>
      </div>
      <span>Messages <span class="notif-badge">3</span></span>
    </div>
  </div>

  <div class="contenu">
    <div class="kpi-grille">
      <div class="kpi">
        <div class="kpi-valeur or">15,2</div>
        <div class="kpi-label">Moyenne générale / 20</div>
      </div>
      <div class="kpi">
        <div class="kpi-valeur vert">8e</div>
        <div class="kpi-label">Rang de classe</div>
      </div>
      <div class="kpi">
        <div class="kpi-valeur rouge">2</div>
        <div class="kpi-label">Absences ce trimestre</div>
      </div>
      <div class="kpi">
        <div class="kpi-valeur">T1</div>
        <div class="kpi-label">Trimestre en cours</div>
      </div>
    </div>

    <div class="grille-2">
      <div class="carte">
        <div class="carte-entete">
          <h3>Notes — Trimestre 1</h3>
          <a href="#">Voir tout</a>
        </div>
        <table>
          <thead><tr><th>Matière</th><th>Note</th><th>Coef.</th><th>Mention</th></tr></thead>
          <tbody>
            <tr><td>Mathématiques</td><td><strong>17/20</strong></td><td>7</td><td><span class="note-badge tb">Très Bien</span></td></tr>
            <tr><td>Sciences de la Vie</td><td><strong>15/20</strong></td><td>6</td><td><span class="note-badge b">Bien</span></td></tr>
            <tr><td>Français</td><td><strong>14/20</strong></td><td>4</td><td><span class="note-badge ab">Assez Bien</span></td></tr>
            <tr><td>Philosophie</td><td><strong>13/20</strong></td><td>3</td><td><span class="note-badge ab">Assez Bien</span></td></tr>
            <tr><td>Histoire-Géographie</td><td><strong>16/20</strong></td><td>3</td><td><span class="note-badge tb">Très Bien</span></td></tr>
            <tr><td>Anglais</td><td><strong>11/20</strong></td><td>3</td><td><span class="note-badge p">Passable</span></td></tr>
          </tbody>
        </table>
        <div class="moy-generale">
          <span class="moy-lbl">Moyenne générale</span>
          <span class="moy-val">15,2 / 20</span>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:1.5rem">
        <div class="carte">
          <div class="carte-entete"><h3>Absences</h3><a href="#">Détails</a></div>
          <div class="absence-liste">
            <div class="absence-item"><div><div style="font-size:13px;font-weight:500">Mercredi 9 oct.</div><div class="absence-date">Matin · Mathématiques</div></div><span class="absence-statut abs-justif">Justifiée</span></div>
            <div class="absence-item"><div><div style="font-size:13px;font-weight:500">Lundi 21 oct.</div><div class="absence-date">Après-midi · Anglais</div></div><span class="absence-statut abs-injust">Non justifiée</span></div>
          </div>
        </div>

        <div class="carte">
          <div class="carte-entete"><h3>Messages récents</h3><a href="#">Messagerie</a></div>
          <div class="msg-liste">
            <div class="msg-item msg-non-lu">
              <div class="msg-initiale">P</div>
              <div style="flex:1"><div class="msg-nom">Prof. de Mathématiques</div><div class="msg-apercu">Progression excellente ce trimestre...</div></div>
              <div class="msg-date">Auj.</div>
            </div>
            <div class="msg-item">
              <div class="msg-initiale" style="background:var(--or)">D</div>
              <div style="flex:1"><div class="msg-nom">Direction CCPA</div><div class="msg-apercu">Réunion parents-professeurs le 15 nov.</div></div>
              <div class="msg-date">Hier</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>