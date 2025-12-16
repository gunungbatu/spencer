<form method="post">
        <div class="card" style="border-top:5px solid #C5A059; margin-top:30px;">
            <div class="card-header" style="display:flex; justify-content:space-between; align-items:center;">
                <span><i class="fas fa-star"></i> REVIEW MANAGER</span>
                <button type="submit" name="save_reviews" class="btn-save" style="width:auto; padding:8px 20px; font-size:0.8rem;">SIMPAN PERUBAHAN</button>
            </div>
            
            <div style="display:flex; flex-direction:column; gap:15px; margin-top:20px;">
            <?php foreach($reviews as $i=>$r): ?>
                <div style="background:#f8f9fa; border:1px solid #e9ecef; padding:15px; border-radius:8px; display:flex; gap:20px; align-items:flex-start; <?php if(isset($r['visible']) && $r['visible']) echo 'border-left:5px solid #1B4D3E;'; ?>">
                    
                    <input type="hidden" name="rev_id[]" value="<?php echo $r['id']; ?>">
                    <input type="hidden" name="rev_date[]" value="<?php echo $r['date']; ?>">
                    <input type="hidden" name="rev_source[]" value="<?php echo $r['source']; ?>">

                    <div style="flex:1;">
                        <div style="font-weight:bold; color:#1B4D3E; font-size:1.1rem; margin-bottom:5px;">
                            <?php echo $r['name']; ?> 
                            <span style="font-size:0.7rem; background:#ddd; padding:2px 6px; border-radius:4px; color:#555; vertical-align:middle; margin-left:5px;"><?php echo strtoupper($r['source']); ?></span>
                        </div>
                        
                        <div style="margin-bottom:10px;">
                            <select name="rev_rating[]" style="padding:5px; border:1px solid #ddd; border-radius:4px; background:white;">
                                <option value="5" <?php echo ($r['rating']==5)?'selected':''; ?>>⭐⭐⭐⭐⭐</option>
                                <option value="4" <?php echo ($r['rating']==4)?'selected':''; ?>>⭐⭐⭐⭐</option>
                                <option value="3" <?php echo ($r['rating']==3)?'selected':''; ?>>⭐⭐⭐</option>
                            </select>
                        </div>

                        <textarea name="rev_comment[]" style="width:100%; height:60px; font-size:0.9rem; padding:8px; border:1px solid #ccc; border-radius:4px;"><?php echo $r['comment']; ?></textarea>
                    </div>

                    <div style="width:150px; display:flex; flex-direction:column; gap:10px;">
                        
                        <label style="cursor:pointer; display:flex; align-items:center; gap:10px; font-weight:bold; font-size:0.8rem; color:#1B4D3E;">
                            <input type="checkbox" name="rev_vis[<?php echo $i; ?>]" value="1" <?php if($r['visible']) echo 'checked'; ?> style="width:20px; height:20px; accent-color:#1B4D3E;">
                            TAMPILKAN?
                        </label>

                        <label style="cursor:pointer; display:flex; align-items:center; gap:10px; font-size:0.8rem; color:#dc3545; margin-top:5px;">
                            <input type="checkbox" name="del_<?php echo $r['id']; ?>" style="width:18px; height:18px; accent-color:#dc3545;">
                            <i class="fas fa-trash"></i> Hapus Review
                        </label>

                    </div>
                </div>
            <?php endforeach; ?>
            </div>
            
            <div style="margin-top:30px; padding-top:20px; border-top:2px dashed #ddd;">
                <h4 style="color:#C5A059; margin-top:0;">+ Tambah Review Manual (Google Maps)</h4>
                <div style="display:grid; grid-template-columns: 1fr 2fr 1fr; gap:10px;">
                    <select name="new_source" style="padding:10px;"><option value="google">Sumber: Google Maps</option><option value="website">Manual Input</option></select>
                    <input type="text" name="new_name" placeholder="Nama Tamu" style="padding:10px;">
                    <select name="new_rating" style="padding:10px;"><option value="5">⭐⭐⭐⭐⭐</option><option value="4">⭐⭐⭐⭐</option></select>
                </div>
                <textarea name="new_comment" placeholder="Paste komentar tamu disini..." style="width:100%; margin-top:10px; height:60px; padding:10px;"></textarea>
            </div>
        </div>
    </form>
