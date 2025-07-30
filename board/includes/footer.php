</main>
</div>
<div class="table-footer">
    <div class="table-info">
        Affichage de <span id="startItem">1</span> à <span id="endItem">10</span> sur <span
            id="totalItems"><?= count($messages) ?></span> entrées
    </div>

    <div class="pagination" id="pagination">
        <button id="firstBtn" class="page-btn" disabled>
            <i class="fas fa-angle-double-left"></i>
        </button>
        <button id="prevBtn" class="page-btn" disabled>
            <i class="fas fa-angle-left"></i>
        </button>
        <div id="pageNumbers" class="page-numbers"></div>
        <button id="nextBtn" class="page-btn">
            <i class="fas fa-angle-right"></i>
        </button>
        <button id="lastBtn" class="page-btn">
            <i class="fas fa-angle-double-right"></i>
        </button>
    </div>
</div>
</div>
</main>
</div>

<script src="../script2.js"></script>
</body>

</html>