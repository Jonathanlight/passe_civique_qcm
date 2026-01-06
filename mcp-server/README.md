# Passe Civique MCP Server

Serveur MCP (Model Context Protocol) pour generer des questions de citoyennete francaise avec Claude AI.

## Installation

```bash
cd mcp-server
npm install
```

## Configuration

Definir la variable d'environnement `ANTHROPIC_API_KEY`:

```bash
export ANTHROPIC_API_KEY="votre-cle-api"
```

## Integration Claude Code

Ajouter dans votre fichier `~/.claude/claude_desktop_config.json`:

```json
{
  "mcpServers": {
    "passe-civique": {
      "command": "node",
      "args": ["/chemin/vers/passe_civique_qcm/mcp-server/index.js"],
      "env": {
        "ANTHROPIC_API_KEY": "votre-cle-api",
        "SYMFONY_PATH": "/chemin/vers/passe_civique_qcm/app/symfony"
      }
    }
  }
}
```

## Outils disponibles

### list_themes
Liste tous les themes disponibles pour les questions.

### generate_questions
Genere des questions pour un theme specifique.
- `themeId`: ID du theme (1-8)
- `count`: Nombre de questions (defaut: 10)
- `difficulty`: easy, medium, hard

### generate_full_exam
Genere un examen complet avec questions pour tous les themes.
- `questionsPerTheme`: Questions par theme (defaut: 5)

### create_exam_config
Cree une configuration de quiz en mode examen.
- `name`: Nom de la configuration
- `questionsCount`: Nombre de questions (defaut: 40)
- `timeLimitMinutes`: Limite de temps (defaut: 45)

### save_questions
Sauvegarde les questions dans un fichier JSON.

## Utilisation manuelle

```bash
# Demarrer le serveur
npm start

# Mode developpement (hot reload)
npm run dev
```
