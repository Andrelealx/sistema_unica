<!-- Modal Adicionar -->
<div class="modal fade" id="adicionarModal">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus"></i> Novo Produto</h5>
                <button type="button" class="close text-light" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nome do Produto</label>
                        <input type="text" name="produto" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Quantidade Inicial</label>
                        <input type="number" name="quantidade" class="form-control" min="0" value="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="adicionar_produto" class="btn btn-success">Salvar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal fade" id="editarModal">
    <div class="modal-dialog">
        <div class="modal-content bg-dark">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-edit"></i> Editar Produto</h5>
                <button type="button" class="close text-light" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>ID do Produto</label>
                        <input type="number" name="produto_id_editar" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Novo Nome</label>
                        <input type="text" name="novo_produto" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Nova Quantidade</label>
                        <input type="number" name="nova_quantidade" class="form-control" min="0" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="editar_produto" class="btn btn-warning">Atualizar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Fechar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Demais modais seguem mesma estrutura -->