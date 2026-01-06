#!/usr/bin/env node

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ListToolsRequestSchema,
} from '@modelcontextprotocol/sdk/types.js';
import Anthropic from '@anthropic-ai/sdk';
import fs from 'fs/promises';
import path from 'path';

const ANTHROPIC_API_KEY = process.env.ANTHROPIC_API_KEY;
const SYMFONY_PATH = process.env.SYMFONY_PATH || path.join(process.cwd(), '..', 'app', 'symfony');

const anthropic = new Anthropic({
  apiKey: ANTHROPIC_API_KEY,
});

// Theme definitions for French citizenship
const THEMES = [
  {
    id: 1,
    name: "Histoire de France",
    description: "Les grandes dates et evenements de l'histoire francaise",
    topics: ["Revolution francaise", "Guerres mondiales", "Ve Republique", "Rois de France", "Napoleon", "Colonisation et decolonisation"]
  },
  {
    id: 2,
    name: "Geographie",
    description: "Le territoire francais et ses caracteristiques",
    topics: ["Regions", "Fleuves", "Montagnes", "DOM-TOM", "Pays frontaliers", "Grandes villes"]
  },
  {
    id: 3,
    name: "Institutions",
    description: "Le fonctionnement de l'Etat et des institutions",
    topics: ["President", "Gouvernement", "Parlement", "Conseil constitutionnel", "Collectivites territoriales", "Justice"]
  },
  {
    id: 4,
    name: "Valeurs de la Republique",
    description: "Les principes fondamentaux de la Republique francaise",
    topics: ["Liberte", "Egalite", "Fraternite", "Laicite", "Droits de l'Homme", "Devise et symboles"]
  },
  {
    id: 5,
    name: "Symboles nationaux",
    description: "Les symboles et emblemes de la France",
    topics: ["Drapeau", "Hymne national", "Marianne", "Coq gaulois", "Fete nationale", "Devise"]
  },
  {
    id: 6,
    name: "Culture et patrimoine",
    description: "La culture, les arts et le patrimoine francais",
    topics: ["Litterature", "Arts", "Monuments", "Gastronomie", "Langue francaise", "UNESCO"]
  },
  {
    id: 7,
    name: "Droits et devoirs",
    description: "Les droits et obligations des citoyens",
    topics: ["Droit de vote", "Service civique", "Impots", "Respect des lois", "Egalite homme-femme", "Protection sociale"]
  },
  {
    id: 8,
    name: "Europe et international",
    description: "La France dans l'Europe et le monde",
    topics: ["Union europeenne", "ONU", "OTAN", "Francophonie", "Zone euro", "Schengen"]
  }
];

async function generateQuestions(themeId, count = 10, difficulty = 'medium') {
  const theme = THEMES.find(t => t.id === themeId);
  if (!theme) {
    throw new Error(`Theme with id ${themeId} not found`);
  }

  const difficultyInstructions = {
    easy: "Questions simples avec des reponses evidentes pour les personnes ayant une connaissance de base.",
    medium: "Questions de difficulte moyenne necessitant une bonne connaissance generale.",
    hard: "Questions difficiles necessitant une connaissance approfondie du sujet."
  };

  const prompt = `Tu es un expert en citoyennete francaise. Genere exactement ${count} questions QCM pour le theme "${theme.name}".

Theme: ${theme.name}
Description: ${theme.description}
Sujets a couvrir: ${theme.topics.join(', ')}
Difficulte: ${difficulty} - ${difficultyInstructions[difficulty]}

IMPORTANT: Retourne UNIQUEMENT un JSON valide, sans texte avant ou apres.

Format JSON requis:
{
  "questions": [
    {
      "content": "La question complete",
      "explanation": "Explication pedagogique de la bonne reponse",
      "difficulty": "${difficulty}",
      "isMultipleChoice": false,
      "answers": [
        {"content": "Reponse A", "isCorrect": true},
        {"content": "Reponse B", "isCorrect": false},
        {"content": "Reponse C", "isCorrect": false},
        {"content": "Reponse D", "isCorrect": false}
      ]
    }
  ]
}

Regles:
- Chaque question doit avoir exactement 4 reponses
- Une seule reponse correcte par question (sauf si isMultipleChoice est true)
- Les questions doivent etre variees et couvrir differents sujets du theme
- Les reponses incorrectes doivent etre plausibles
- L'explication doit etre educative et aider a memoriser
- Evite les questions pieges ou ambigues
- Utilise un francais correct sans accents speciaux dans le JSON`;

  const message = await anthropic.messages.create({
    model: 'claude-sonnet-4-20250514',
    max_tokens: 4096,
    messages: [
      {
        role: 'user',
        content: prompt
      }
    ]
  });

  const responseText = message.content[0].text;

  // Extract JSON from response
  let jsonStr = responseText;
  const jsonMatch = responseText.match(/\{[\s\S]*\}/);
  if (jsonMatch) {
    jsonStr = jsonMatch[0];
  }

  const data = JSON.parse(jsonStr);

  return {
    theme: theme,
    questions: data.questions
  };
}

async function createExamConfiguration(name, questionsCount = 40, timeLimitMinutes = 45) {
  return {
    name: name,
    description: `Configuration d'examen officiel avec ${questionsCount} questions en ${timeLimitMinutes} minutes`,
    questionsCount: questionsCount,
    minimumCorrectAnswers: Math.ceil(questionsCount * 0.75),
    minimumScorePercent: "75.00",
    timeLimitMinutes: timeLimitMinutes,
    shuffleQuestions: true,
    shuffleAnswers: true,
    showExplanationAfterAnswer: false,
    showResultsAtEnd: true,
    allowRetake: true,
    isDefault: false,
    isActive: true
  };
}

async function generateFullExam(questionsPerTheme = 5) {
  const allQuestions = [];

  for (const theme of THEMES) {
    console.error(`Generating questions for theme: ${theme.name}`);
    const result = await generateQuestions(theme.id, questionsPerTheme, 'medium');
    allQuestions.push(...result.questions.map(q => ({
      ...q,
      themeId: theme.id,
      themeName: theme.name
    })));
  }

  return {
    totalQuestions: allQuestions.length,
    questions: allQuestions,
    themes: THEMES
  };
}

async function saveQuestionsToFile(questions, filename) {
  const outputPath = path.join(SYMFONY_PATH, 'var', 'generated', filename);
  await fs.mkdir(path.dirname(outputPath), { recursive: true });
  await fs.writeFile(outputPath, JSON.stringify(questions, null, 2), 'utf-8');
  return outputPath;
}

// Create MCP Server
const server = new Server(
  {
    name: 'passe-civique-mcp',
    version: '1.0.0',
  },
  {
    capabilities: {
      tools: {},
    },
  }
);

// List available tools
server.setRequestHandler(ListToolsRequestSchema, async () => {
  return {
    tools: [
      {
        name: 'list_themes',
        description: 'Liste tous les themes disponibles pour les questions de citoyennete',
        inputSchema: {
          type: 'object',
          properties: {},
          required: []
        }
      },
      {
        name: 'generate_questions',
        description: 'Genere des questions QCM pour un theme specifique en utilisant Claude AI',
        inputSchema: {
          type: 'object',
          properties: {
            themeId: {
              type: 'number',
              description: 'ID du theme (1-8)'
            },
            count: {
              type: 'number',
              description: 'Nombre de questions a generer (defaut: 10)',
              default: 10
            },
            difficulty: {
              type: 'string',
              enum: ['easy', 'medium', 'hard'],
              description: 'Niveau de difficulte',
              default: 'medium'
            }
          },
          required: ['themeId']
        }
      },
      {
        name: 'generate_full_exam',
        description: 'Genere un examen complet avec des questions pour tous les themes',
        inputSchema: {
          type: 'object',
          properties: {
            questionsPerTheme: {
              type: 'number',
              description: 'Nombre de questions par theme (defaut: 5)',
              default: 5
            }
          },
          required: []
        }
      },
      {
        name: 'create_exam_config',
        description: 'Cree une configuration de quiz en mode examen',
        inputSchema: {
          type: 'object',
          properties: {
            name: {
              type: 'string',
              description: 'Nom de la configuration'
            },
            questionsCount: {
              type: 'number',
              description: 'Nombre de questions (defaut: 40)',
              default: 40
            },
            timeLimitMinutes: {
              type: 'number',
              description: 'Limite de temps en minutes (defaut: 45)',
              default: 45
            }
          },
          required: ['name']
        }
      },
      {
        name: 'save_questions',
        description: 'Sauvegarde les questions generees dans un fichier JSON pour import Symfony',
        inputSchema: {
          type: 'object',
          properties: {
            questions: {
              type: 'array',
              description: 'Liste des questions a sauvegarder'
            },
            filename: {
              type: 'string',
              description: 'Nom du fichier (defaut: questions.json)',
              default: 'questions.json'
            }
          },
          required: ['questions']
        }
      }
    ]
  };
});

// Handle tool calls
server.setRequestHandler(CallToolRequestSchema, async (request) => {
  const { name, arguments: args } = request.params;

  try {
    switch (name) {
      case 'list_themes': {
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(THEMES, null, 2)
            }
          ]
        };
      }

      case 'generate_questions': {
        const { themeId, count = 10, difficulty = 'medium' } = args;
        const result = await generateQuestions(themeId, count, difficulty);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(result, null, 2)
            }
          ]
        };
      }

      case 'generate_full_exam': {
        const { questionsPerTheme = 5 } = args;
        const result = await generateFullExam(questionsPerTheme);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(result, null, 2)
            }
          ]
        };
      }

      case 'create_exam_config': {
        const { name: configName, questionsCount = 40, timeLimitMinutes = 45 } = args;
        const config = await createExamConfiguration(configName, questionsCount, timeLimitMinutes);
        return {
          content: [
            {
              type: 'text',
              text: JSON.stringify(config, null, 2)
            }
          ]
        };
      }

      case 'save_questions': {
        const { questions, filename = 'questions.json' } = args;
        const outputPath = await saveQuestionsToFile(questions, filename);
        return {
          content: [
            {
              type: 'text',
              text: `Questions saved to: ${outputPath}`
            }
          ]
        };
      }

      default:
        throw new Error(`Unknown tool: ${name}`);
    }
  } catch (error) {
    return {
      content: [
        {
          type: 'text',
          text: `Error: ${error.message}`
        }
      ],
      isError: true
    };
  }
});

// Start server
async function main() {
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error('Passe Civique MCP Server running on stdio');
}

main().catch(console.error);
