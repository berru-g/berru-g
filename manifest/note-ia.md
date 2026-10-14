# Pourquoi Dieu est le plus gros fdp de l'univers ? Et je vais vous le prouver.

### ou Dieu existe. Il s'appelle la sélection naturelle… et Il n'a jamais été du côté des faibles.

---

## Préface

Notre histoire débute il y'a environ 500 millions d'années, à l'ère édacarian tardif. Le monde ressemble à un immense desert, posé sur une sphére océanique. Les organismes unicellulaires sans predation du jardin d'édiacara ont laissé place à des organisme se nourrisant d'autre organisme. Nous sommes à la naissance de la prédation.

> Première bifurcation dans le code de la vie : quand manger l'autre devient une stratégie viable.

```js
// La naissance de la prédation, en une transition
const eden = {
  epoque: "Édiacara",
  predation: false,
  strategieDominante: "filtrer les nutriments",
};

const nouveauMonde = {
  ...eden,
  predation: true,
  strategieDominante: "manger les autres organismes",
};

console.log(nouveauMonde.prediatisation ? "changement d'ère" : "statu quo");
// => "changement d'ère"
```

Désormais, la sélection naturelle, ne récompense plus seulement les organisme qui savent filtrer les nutriments, mais les organismes capable de développer un cerveau pour survivre en échappant à leur prédateur, en s'adaptant et ainsi perdurer l'espece. Les premières variables de notre code inscrit dans nos cerveau sont donc, détecter, décider et agir. Le premier algorithme est donc "si danger alors fuir", la peur est donc la source de notre première évolution.

```js
// Le premier algorithme inscrit dans nos cerveaux
const cerveauPrimitif = { detecter: null, decider: null, agir: null };

function selectionNaturelle(individu) {
  individu.detecter = "perception de l'environnement";
  individu.decider = "interprétation du danger";
  individu.agir = individu.detecter === "danger"
    ? "fuir"        // si danger alors fuir
    : "continuer";
  return individu;
}

// La peur est la source de notre première évolution
console.log(selectionNaturelle(cerveauPrimitif).agir); // => "fuir"
```

La sélection naturelle est peut etre ce que l'on appel dieu, c'est cette fameuse créatrice de loi concernant les etre vivant de cette planete. Celle qui code la vie, inscrit ces algorithmes, qui décide qui continue de vivre, en restant indifferente face à l'injustice et l'impuissance de cetrain organisme incapable de s'adapter rapidement aux changement de leur environnement. Car la première loi immuable est, survivre et se reproduire.

```js
// La première loi immuable
const LOI_IMMUABLE = { survivre: true, seReproduire: true };

function dieu(organisme) {
  // aucune notion de bien ou de mal : seulement le verdict
  const adapte = organisme.suitLaLoi(LOI_IMMUABLE);
  return adapte ? organisme.perdure() : organisme.disparait();
}

console.log(dieu({ suitLaLoi: () => false })); // => undefined (extinction)
```

La toute puissance de la selection naturelle, que nos ancêtre, dans differents courant religieux et spirituel nous ont transmis pour que nous surviviont à notre tour. Comme la loi de matthieu dans les evangile qui prévient "a celui qui à tout, tout lui sera données, à celui qui n'as rien, tout lui sera enlevé", traduit dans le monde moderne ça donne "adapte toi ou crève".

```js
// Loi de Matthieu, traduite en algorithme de sélection
function loiDeMatthieu(organisme) {
  if (organisme.aTout) {
    return "tout lui sera donné";
  }
  if (organisme.nARien) {
    return "tout lui sera enlevé";
  }
}

function mondeModerne(organisme) {
  return organisme.sadapte ? "survie" : "adapte toi ou crève";
}
```

La genèse de l'être humain est donc là, la sélection naturelle encourage les fort et defonce les faibles. Aucune notion de bien et mal, de valeurs, dieu s'en tape. Et on s'étonne encore que dieu ne réponde pas a nos prières. La raison est simple, dieu est indifférence tout comme la sélection naturelle et personne ne viendra te sauver, a part toi, si tu le décide.

```js
// Dieu ne répond pas aux prières : l'indifférence en code
function priere(cri) {
  const selectionNaturelle = { compassion: undefined };
  return selectionNaturelle.compassion ?? null; // => null
}

// La seule fonction qui sauve
function sauvetage(moi) {
  if (moi.leDecide) {
    return "je me sauve moi-même";
  }
  return personne; // personne ne viendra te sauver
}
```

---

*Fin de la préface.*

# Chapitre 2 : Si je suis arrivé a cette conclusion seul apres 39ans d'existence, tu a le droit d'en douter, mais si des écrivains reconnu pensent la même choses, alors tu devrais peut être arrêter de croire que dieu viendra te sauver.

J'imagine déjà le lecteur, attablé devant son comptoir, qui sourit en me lisant. Le mec qui écrit que dieu est la sélection naturelle, franchement, on a vu des prophètes plus crédibles. Et il a raison de douter. Si je suis arrivé a cette conclusion seul apres 39ans d'existence, tu a le droit d'en douter. Je n'ai pas de doctorat en biologie, je n'ai jamais disséqué un ver de terre, et pourtant.

Le probleme, c'est que je ne suis pas seul. Et ça, ça change tout.

Prenez Richard Dawkins, biologiste a Oxford, pas exactement un illuminé de comptoir. Il a écrit que la sélection naturelle est "l'horloger aveugle", un processus "aveugle, inconscient, automatique", qui "n'a aucun but en tete, ni vision, ni prévoyance". Et il conclut que l'univers que nous observons possede "ni dessein, ni but, ni mal ni bien, rien qu'une indifférence aveugle et sans pitié". Relisez ma préface. J'y écris que dieu est indifférence. Lui écrit que l'univers est une indifférence sans pitié. Nous avons juste un désaccord sur le prénom.

Prenez Charles Darwin lui même, le monsieur du domaine. A la fin de l'Origine des espèces, il ne parle pas d'harmonie, il parle de "la guerre de la nature, la famine et la mort". Voila le programme. Pas un mot sur la miséricorde.

Prenez Herbert Spencer, qui a inventé la formule "survie du plus apte" et qui pensait que cette loi s'appliquait aussi aux sociétés humaines. Ma traduction moderne, "adapte toi ou crève", il l'a écrite il y a cent cinquante ans, en plus poli, avec des chromosomes en moins.

Prenez Robert K. Merton, sociologue, qui a formalisé ce que j'appel la loi de matthieu : les advantagés du départ cumulent tout, et ceux qui n'ont rien perdent même ce qu'ils ont. Il l'a nommé "l'effet Matthieu", justement en référence a la parabole des evangiles que je citais en préface. Quand la science officialise une parabole biblique comme loi de l'inégalité, il faut se poser des questions sur qui, des évangiles ou de la sélection naturelle, a copié sur l'autre.

Prenez Patrick Tort, philosophe francais, qui a consacré des milliers de pages a démontrer que la sélection naturelle est une loi d'élimination, sans progrès moral, sans boussole, et que tous ceux qui ont voulu en faire une morale ont mal lu Darwin.

Et prenez enfin Nietzsche, qui n'avait pas encore lu Darwin correctement mais qui avait compris le tableau : dieu est mort, l'univers est au dela du bien et du mal, et personne, absolument personne, ne porte de secours. Le surhomme nietzschéen, c'est exactement le "a part toi, si tu le décide" de ma préface. Il l'appelait volonté, j'appelle ça décision, le fdp du comptoir et le philosophe aux moustaches disent la même chose avec des niveaux de vin différents.

Donc voila mon pari. Si je suis arrivé a cette conclusion seul apres 39ans d'existence, tu a le droit d'en douter. Mais si des écrivains reconnu, un biologiste d'Oxford, le fondateur de la théorie de l'évolution, un sociologue de Columbia, un philosophe allemand dont on lit encore les livres cent quarante ans apres sa mort, pensent la même choses, alors tu devrais peut être arrêter de croire que dieu viendra te sauver.

Ils ne sont pas d'accord entre eux sur tout, evidemment. Dawkins déteste Spencer, Tort déteste tout le monde, et Nietzsche aurait trouvé tous les autres bien trop sages. Mais tous, sans exception, tombent sur le même os : la sélection naturelle ne t'aime pas, ne t'écoute pas, et ne répondra pas a tes prières. Le silence que tu entends quand tu pries, ce n'est pas un test de foi. C'est l'algorithme qui tourne, et qui ne t'a pas dans ses variables.

```js
// Vérification croisée : quand des esprits isolés convergent
const penseurs = [
  { nom: "Darwin",     thèse: "la guerre de la nature, la famine et la mort" },
  { nom: "Spencer",    thèse: "survie du plus apte" },
  { nom: "Nietzsche",  thèse: "dieu est mort, sauve toi toi même" },
  { nom: "Dawkins",    thèse: "l'horloger aveugle, indifférence sans pitié" },
  { nom: "Merton",     thèse: "l'effet Matthieu, avantages cumulés" },
  { nom: "Tort",       thèse: "une loi d'élimination sans morale" },
  { nom: "moi, au comptoir", thèse: "dieu est la sélection naturelle, indifférente" },
];

const consensus = penseurs.filter(p => p.thèse.includes("indifféren") || p.thèse.includes("famine") || p.thèse.includes("sauve") || p.thèse.includes("élimination") || p.thèse.includes("aveugle") || p.thèse.includes("plus apte") || p.thèse.includes("Matthieu"));

console.log(consensus.length + " hommes, un seul silence côté ciel");
// => "7 hommes, un seul silence côté ciel"
```

La question qui reste ouverte, et qui fera peut être un chapitre 3, est la suivante : maintenant qu'on sait que le code ne nous aime pas, qu'est ce qu'on en fait ? La sélection naturelle ne connait pas la pitié, c'est un fait. Mais nous, nous avons inventé la médecine, les filets de sécurité, les Restos du Cœur. Nous sommes les seuls organismes de l'histoire de la vie a avoir entrepris de corriger le code. Est ce que c'est trahir la loi, ou la dépasser ? Est ce que c'est la plus grande arrogance de l'espèce, ou enfin l'entrée de l'humanité dans ses propres variables, je ne sais pas encore. Mais je sais une chose : cette décision non plus, personne ne la prendra a notre place.

---

*Fin du chapitre 2.*