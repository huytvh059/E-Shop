# Giả định nghiệp vụ

- Giá VND nguyên DECIMAL(15,0); PHP dùng số nguyên, yêu cầu hệ 64-bit. Không phí giao hàng/thuế riêng vì đề bài không quy định.
- Giỏ/so sánh session, đăng nhập trước checkout. Thêm giỏ không giữ tồn.
- Cùng loại được hiểu là cùng danh mục. So sánh giữ tối đa 4 sản phẩm.
- User và staff tách bảng, đăng nhập riêng. Admin chỉ có một role mỗi nhân viên; không cấp quyền tùy biến ngoài năm vai trò.
- Chuỗi trạng thái: pending → processing → shipping → completed. Có thể hủy từ pending/processing/shipping; không hủy completed/cancelled.
- Hủy hoàn tồn trong cùng transaction. Nếu đã thu tiền, accountant phải ghi nhận hoàn tiền riêng, không tự gọi ngân hàng.
- Sổ thanh toán dạng sự kiện bất biến. Paid/refunded cộng/trừ tiền thật; unpaid chỉ ghi chú, không cộng vào thực thu. Không chỉnh/xóa lịch sử giao dịch qua UI; sửa sai bằng bút toán phù hợp.
- Không cho thu vượt tổng đơn, không cho hoàn vượt tiền đã thu ròng; đơn hủy không được thu thêm.
- Doanh thu đơn hoàn thành tính theo thời điểm lịch sử chuyển completed; tiền thu/hoàn theo paid_at. Vì khác cơ sở thời gian nên có thể khác nhau, báo cáo hiển thị riêng.
- Hóa đơn là chứng từ nội bộ, không thay hóa đơn điện tử thuế. Một hóa đơn/đơn, snapshot lấy từ order/items bất biến.
- Xóa sản phẩm là ngừng bán để bảo toàn lịch sử. Danh mục/hãng có tham chiếu không xóa được.
- Tồn ban đầu khi tạo sản phẩm = 0; tăng tồn qua phiếu nhập. Mỗi phiếu có lý do/người thực hiện, không sửa/xóa sổ kho.
- Ảnh seed là minh họa chung theo loại, không khẳng định ảnh đúng model. Ảnh upload JPEG/PNG/WebP tối đa 5 MB, 20 MP, 8 ảnh/lần; SVG demo do dự án tạo, không nhận SVG người dùng.
- Khi bỏ ảnh trong admin chỉ xóa liên kết DB, giữ file vật lý để tránh mất ảnh do rollback; dọn orphan chỉ sau backup và đối chiếu thủ công.
- PHP và MariaDB dùng +07:00. Log ứng dụng lưu metadata lỗi, không lưu password/token/thông tin kết nối.
- Giới hạn đăng nhập: 5 lỗi/15 phút theo email + IP + loại tài khoản; lỗi hiển thị chung.
- CDN Bootstrap/Chart.js/Google Fonts cần Internet ở trình duyệt, không có SPA hoặc dịch vụ ảnh ngoài.
