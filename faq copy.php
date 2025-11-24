<?php
include "core.php"; // Connexion BDD + Paramètres
head(); // En-tête du site (Menu, CSS...)

// Récupérer les questions actives
$q_faq = mysqli_query($connect, "SELECT * FROM faqs WHERE active='Yes' ORDER BY position_order ASC, id DESC");
?>

<div class="container mt-5 mb-5">
    <div class="row justify-content-center">
        <div class="col-md-10">
            
            <div class="text-center mb-5">
                <h1 class="fw-bold"><i class="fas fa-question-circle text-primary"></i> Frequently Asked Questions</h1>
                <p class="lead text-muted">Find answers to your most common questions here.</p>
            </div>

            <?php if (mysqli_num_rows($q_faq) > 0): ?>
                
                <div class="accordion shadow-sm" id="faqAccordion">
                    <?php 
                    $i = 0;
                    while ($row = mysqli_fetch_assoc($q_faq)): 
                        $i++;
                        $collapseId = "collapse_" . $row['id'];
                        $headingId  = "heading_" . $row['id'];
                        
                        // Le premier élément est ouvert par défaut (optionnel, j'ai mis 'show' uniquement si $i==1)
                        $showClass   = ($i == 1) ? "show" : "";
                        $buttonClass = ($i == 1) ? "" : "collapsed";
                    ?>
                    
                    <div class="accordion-item border-0 mb-2 shadow-sm rounded overflow-hidden">
                        <h2 class="accordion-header" id="<?php echo $headingId; ?>">
                            <button class="accordion-button <?php echo $buttonClass; ?> fw-bold py-3" type="button" 
                                    data-bs-toggle="collapse" data-bs-target="#<?php echo $collapseId; ?>" 
                                    aria-expanded="<?php echo ($i == 1 ? 'true' : 'false'); ?>" aria-controls="<?php echo $collapseId; ?>">
                                <i class="fas fa-angle-right me-2 text-primary"></i> <?php echo htmlspecialchars($row['question']); ?>
                            </button>
                        </h2>
                        <div id="<?php echo $collapseId; ?>" class="accordion-collapse collapse <?php echo $showClass; ?>" 
                             aria-labelledby="<?php echo $headingId; ?>" data-bs-parent="#faqAccordion">
                            <div class="accordion-body bg-light text-dark" style="line-height: 1.7;">
                                <?php echo html_entity_decode($row['answer']); ?>
                            </div>
                        </div>
                    </div>

                    <?php endwhile; ?>
                </div>

            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle"></i> No questions have been added yet.
                </div>
            <?php endif; ?>

            <div class="card mt-5 border-0 bg-light">
                <div class="card-body text-center p-5">
                    <h4 class="card-title">Can't find your answer?</h4>
                    <p class="card-text">Our team is here to help you.</p>
                    <a href="contact.php" class="btn btn-primary px-4"><i class="fas fa-envelope"></i> Contact Us</a>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
/* Petit ajustement CSS pour rendre l'accordéon plus joli */
.accordion-button:not(.collapsed) {
    color: #0d6efd;
    background-color: #e7f1ff;
    box-shadow: inset 0 -1px 0 rgba(0,0,0,.125);
}
.accordion-button:focus {
    box-shadow: none; /* Enlève le contour bleu par défaut au clic */
    border-color: rgba(0,0,0,.125);
}
</style>

<?php
footer(); // Pied de page
?>