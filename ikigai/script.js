function generateIkigai() {
  // Récupère les réponses de l'utilisateur
  const loveAnswer = document.getElementById('loveQuestion').value;
  const hobbiesAnswer = document.getElementById('hobbiesQuestion').value;
  const happyExperiencesAnswer = document.getElementById('happyExperiencesQuestion').value;
  const idealDayAnswer = document.getElementById('idealDayQuestion').value;

  const talentsAnswer = document.getElementById('talentsQuestion').value;
  const recognizedSkillsAnswer = document.getElementById('recognizedSkillsQuestion').value;
  const easyTasksAnswer = document.getElementById('easyTasksQuestion').value;
  const proudAccomplishmentsAnswer = document.getElementById('proudAccomplishmentsQuestion').value;

  const worldIssuesAnswer = document.getElementById('worldIssuesQuestion').value;
  const contributionAnswer = document.getElementById('contributionQuestion').value;
  const skillsResourcesAnswer = document.getElementById('skillsResourcesQuestion').value;
  const solveGlobalIssueAnswer = document.getElementById('solveGlobalIssueQuestion').value;

  const professionalSkillsAnswer = document.getElementById('professionalSkillsQuestion').value;
  const excelProfessionallyAnswer = document.getElementById('excelProfessionallyQuestion').value;
  const valuableServicesAnswer = document.getElementById('valuableServicesQuestion').value;
  const dreamJobAnswer = document.getElementById('dreamJobQuestion').value;

  // Affiche les réponses de l'utilisateur
  const ikigaiResult = document.getElementById('ikigaiResult');
  ikigaiResult.innerHTML = `
    <h2>Your Ikigai</h2>
    <p><strong>Ce que tu aimes :</strong> ${loveAnswer}</p>
    <p><strong>Ce dans quoi tu es bon :</strong> ${talentsAnswer}</p>
    <p><strong>Ce dont le monde a besoin :</strong> ${worldIssuesAnswer}</p>
    <p><strong>Ce pour quoi tu peux être payé :</strong> ${professionalSkillsAnswer}</p>
  `;

  // Affiche un récapitulatif
  const recapContent = document.getElementById('recapContent');
  recapContent.innerHTML = `
    <p><strong>Ce que tu aimes :</strong> ${loveAnswer}, ${hobbiesAnswer}, ${happyExperiencesAnswer}, ${idealDayAnswer}</p>
    <p><strong>Ce dans quoi tu es bon :</strong> ${talentsAnswer}, ${recognizedSkillsAnswer}, ${easyTasksAnswer}, ${proudAccomplishmentsAnswer}</p>
    <p><strong>Ce dont le monde a besoin :</strong> ${worldIssuesAnswer}, ${contributionAnswer}, ${skillsResourcesAnswer}, ${solveGlobalIssueAnswer}</p>
    <p><strong>Ce pour quoi tu peux être payé :</strong> ${professionalSkillsAnswer}, ${excelProfessionallyAnswer}, ${valuableServicesAnswer}, ${dreamJobAnswer}</p>
  `;

  // Affiche le récapitulatif
  document.getElementById('recapContainer').style.display = 'block';
}

// Fonction pour suggérer automatiquement des réponses...




























































/*

Your Ikigai

Ce que tu aimes : créer un projet de A à Z

Ce dans quoi tu es bon : accumuler des competences

Ce dont le monde a besoin : L'injustice et le manque de savoir vivre

Ce pour quoi tu peux être payé : créer de tout piece en independant
Recapitulatif

Ce que tu aimes : créer un projet de A à Z, Créer des choses utile ou non, Aller au bout d'un projet, Apprendre et voyager seul dans mon van ou mon avion

Ce dans quoi tu es bon : accumuler des competences, les relations humaines , imaginer un projet de a à z, aboutir un projet et voir des sourire et de la reconnaissance

Ce dont le monde a besoin : L'injustice et le manque de savoir vivre, en éducant des jeunes et en leur donnant les armes , l'experience de mes echecs et ma bienveillance, la justice d'etre tous ego

Ce pour quoi tu peux être payé : créer de tout piece en independant, l'education alternative, l'education de soit des petit, créer des protheses robotique pour amputer

*/