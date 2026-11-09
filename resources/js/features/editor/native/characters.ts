/**
 * Special characters and emoji to insert, by group, found by name in English
 * or French ("euro", "flèche", "coeur", "check"). No imports, so it runs in Node.
 */

export interface CharacterEntry {
  char: string;
  name: string;
  keywords: string;
  group: CharacterGroup;
}

export type CharacterGroup = 'symbols' | 'punctuation' | 'currency' | 'math' | 'arrows' | 'letters' | 'greek' | 'emoji';

export const GROUPS: CharacterGroup[] = ['emoji', 'symbols', 'punctuation', 'currency', 'math', 'arrows', 'letters', 'greek'];

/** char | name | keywords, one per line, under its group. */
const TABLE: Record<CharacterGroup, string> = {
  emoji: `
😀|grinning face|sourire content smile
😂|tears of joy|rire larmes laugh lol
🙂|slightly smiling face|sourire smile
😉|winking face|clin d'oeil wink
😊|smiling eyes|sourire heureux happy
😍|heart eyes|amour coeur love
🤔|thinking face|reflexion pense think
😐|neutral face|neutre
😢|crying face|triste pleure sad
😡|angry face|colere angry
😴|sleeping face|dort sleep
🤯|mind blown|choc
👍|thumbs up|ok pouce bien like
👎|thumbs down|pouce non dislike
👏|clapping hands|bravo applaudir clap
🙏|folded hands|merci prier please thanks
💪|flexed biceps|force muscle strong
👀|eyes|yeux regarder look
👋|waving hand|salut bonjour hello wave
🤝|handshake|accord poignee de main deal
✅|check mark button|valide fait ok done coche
❌|cross mark|non erreur faux no wrong croix
⚠️|warning|attention danger alerte
⭐|star|etoile favori
✨|sparkles|etincelles nouveau new magic
🔥|fire|feu chaud hot
🎉|party popper|fete bravo celebration
💡|light bulb|idee ampoule idea
📌|pushpin|epingle punaise pin
📎|paperclip|trombone piece jointe attach
📅|calendar|calendrier date
🕒|three o'clock|heure horloge time clock
📈|chart increasing|hausse croissance graphique up
📉|chart decreasing|baisse graphique down
📊|bar chart|graphique statistiques stats
💰|money bag|argent budget money
💶|euro banknote|billet euro argent
🚀|rocket|fusee lancement launch
🎯|direct hit|cible objectif target goal
🏆|trophy|trophee victoire win
❤️|red heart|coeur amour love heart
💬|speech balloon|message commentaire bulle chat
📞|telephone receiver|telephone appel call
📧|e-mail|email courriel mail
🔒|locked|cadenas verrouille securite lock
🔗|link|lien chaine
✏️|pencil|crayon modifier edit
📝|memo|note ecrire
📁|file folder|dossier folder
🗑️|wastebasket|poubelle corbeille delete
🔍|magnifying glass|loupe chercher search
⚙️|gear|reglages parametres settings
💼|briefcase|travail mallette business
🌍|globe|monde terre world
☀️|sun|soleil
🌙|crescent moon|lune nuit
⚡|high voltage|eclair energie
🍀|four leaf clover|chance trefle luck
🎁|gift|cadeau present
☕|hot beverage|cafe the coffee
🐛|bug|insecte bogue
🔔|bell|cloche notification
🧠|brain|cerveau
👉|backhand index right|droite pointer
➡️|right arrow|fleche droite`,
  symbols: `
©|copyright|droit d'auteur
®|registered|marque deposee
™|trade mark|marque
✓|check mark|coche valide
✗|ballot x|croix
★|black star|etoile pleine
☆|white star|etoile vide
♥|heart suit|coeur
♦|diamond suit|carreau
♣|club suit|trefle
♠|spade suit|pique
☎|telephone|telephone
✉|envelope|enveloppe courrier
☑|ballot box with check|case cochee
☐|ballot box|case vide
•|bullet|puce point
◦|white bullet|puce vide
▪|small square|carre
►|right pointer|triangle
♪|eighth note|note musique
°|degree|degre temperature
№|numero sign|numero
℃|degree celsius|celsius
⌘|command key|commande mac
⌥|option key|option alt mac
⇧|shift|majuscule
⏎|return|entree`,
  punctuation: `
«|left guillemet|guillemet francais ouvrant
»|right guillemet|guillemet francais fermant
“|left double quote|guillemet anglais ouvrant
”|right double quote|guillemet anglais fermant
‘|left single quote|apostrophe ouvrante
’|apostrophe|apostrophe typographique
—|em dash|tiret cadratin
–|en dash|tiret demi-cadratin
…|ellipsis|points de suspension
·|middle dot|point median
¶|pilcrow|paragraphe
§|section sign|paragraphe section article
†|dagger|croix obele
‡|double dagger|double croix
‰|per mille|pour mille
′|prime|minute pied
″|double prime|seconde pouce
¡|inverted exclamation|exclamation inversee
¿|inverted question|question inversee
 |no-break space|espace insecable
 |narrow no-break space|espace fine insecable`,
  currency: `
€|euro|euro
£|pound|livre sterling
$|dollar|dollar
¥|yen|yen yuan
¢|cent|centime
₹|indian rupee|roupie
₽|ruble|rouble
₩|won|won
₺|lira|livre turque
₿|bitcoin|bitcoin
₣|franc|franc
¤|currency sign|monnaie`,
  math: `
±|plus minus|plus ou moins
×|multiplication|fois multiplier
÷|division|diviser
≠|not equal|different
≈|almost equal|environ approximativement
≤|less or equal|inferieur ou egal
≥|greater or equal|superieur ou egal
∞|infinity|infini
√|square root|racine carree
∑|sum|somme sigma
∏|product|produit
∫|integral|integrale
∂|partial|derivee partielle
∆|increment|delta variation
∇|nabla|gradient
π|pi|pi
µ|micro|micro
¹|superscript one|exposant un
²|superscript two|carre exposant deux
³|superscript three|cube exposant trois
¼|one quarter|quart
½|one half|demi moitie
¾|three quarters|trois quarts
∈|element of|appartient
∉|not element of|n'appartient pas
∩|intersection|inter
∪|union|union
⊂|subset|inclus sous-ensemble
∀|for all|pour tout quel que soit
∃|there exists|il existe
¬|not|negation non
∧|and|et logique
∨|or|ou logique
⇒|implies|implique donc
⇔|if and only if|equivalent`,
  arrows: `
←|left arrow|fleche gauche
→|right arrow|fleche droite
↑|up arrow|fleche haut
↓|down arrow|fleche bas
↔|left right arrow|fleche double horizontale
↕|up down arrow|fleche double verticale
⇐|left double arrow|double fleche gauche
⇒|right double arrow|double fleche droite
⇑|up double arrow|double fleche haut
⇓|down double arrow|double fleche bas
↗|north east arrow|fleche diagonale haut droite
↘|south east arrow|fleche diagonale bas droite
↩|return arrow|retour
↪|forward arrow|renvoi
⟶|long right arrow|longue fleche droite`,
  letters: `
À|A grave|a accent grave majuscule
Â|A circumflex|a accent circonflexe majuscule
Ç|C cedilla|c cedille majuscule
É|E acute|e accent aigu majuscule
È|E grave|e accent grave majuscule
Ê|E circumflex|e accent circonflexe majuscule
Ë|E diaeresis|e trema majuscule
Î|I circumflex|i accent circonflexe majuscule
Ï|I diaeresis|i trema majuscule
Ô|O circumflex|o accent circonflexe majuscule
Ù|U grave|u accent grave majuscule
Û|U circumflex|u accent circonflexe majuscule
Ü|U diaeresis|u trema majuscule
Ÿ|Y diaeresis|y trema majuscule
Œ|OE ligature|oe e dans l'o majuscule
œ|oe ligature|oe e dans l'o
Æ|AE ligature|ae e dans l'a majuscule
æ|ae ligature|ae e dans l'a
ß|sharp s|eszett allemand
ñ|n tilde|n tilde espagnol
ø|o stroke|o barre
å|a ring|a rond
ð|eth|eth
þ|thorn|thorn`,
  greek: `
α|alpha|alpha
β|beta|beta
γ|gamma|gamma
δ|delta|delta
ε|epsilon|epsilon
θ|theta|theta
λ|lambda|lambda
μ|mu|mu
π|pi|pi
ρ|rho|rho
σ|sigma|sigma
τ|tau|tau
φ|phi|phi
χ|chi|chi
ψ|psi|psi
ω|omega|omega
Γ|capital gamma|gamma majuscule
Δ|capital delta|delta majuscule
Θ|capital theta|theta majuscule
Λ|capital lambda|lambda majuscule
Σ|capital sigma|sigma majuscule
Φ|capital phi|phi majuscule
Ω|capital omega|omega majuscule ohm`,
};

export const CHARACTERS: CharacterEntry[] = (Object.keys(TABLE) as CharacterGroup[]).flatMap((group) =>
  TABLE[group]
    .trim()
    .split('\n')
    .map((line) => {
      const [char, name, keywords = ''] = line.split('|');
      return { char, name, keywords, group };
    })
);

const fold = (text: string): string => text.toLowerCase().normalize('NFD').replace(/[̀-ͯ]/g, '');

/**
 * The characters a search keeps — every word of it found in the name or the
 * keywords, names starting with it first — or a whole group when there is no
 * search.
 */
export function findCharacters(query: string, group: CharacterGroup | null = null, limit = 120): CharacterEntry[] {
  const words = fold(query.trim()).split(/\s+/).filter(Boolean);
  if (!words.length) return CHARACTERS.filter((entry) => !group || entry.group === group).slice(0, limit);

  return CHARACTERS.map((entry, index) => {
    const name = fold(entry.name);
    const haystack = `${name} ${fold(entry.keywords)}`;
    if (!words.every((word) => haystack.includes(word) || entry.char === word)) return null;
    // The name itself first, then names starting with the search, then any word that does.
    const query = words.join(' ');
    const score = name === query ? -1 : name.startsWith(words[0]) ? 0 : haystack.split(/\s+/).some((token) => token.startsWith(words[0])) ? 1 : 2;
    return { entry, score, index };
  })
    .filter((found): found is { entry: CharacterEntry; score: number; index: number } => found !== null)
    .sort((a, b) => a.score - b.score || a.index - b.index)
    .slice(0, limit)
    .map((found) => found.entry);
}
