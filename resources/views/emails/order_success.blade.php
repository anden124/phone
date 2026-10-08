<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Xác nhận đơn hàng</title>
</head>
<body style="font-family: Arial, sans-serif; color: #333;">
    <h2>Xin chào {{ $order->name }},</h2>

    <p>Cảm ơn bạn đã đặt hàng tại <strong>Phone Store</strong>.</p>
    <p>Đơn hàng của bạn đã được ghi nhận thành công.</p>

    <h3>Thông tin đơn hàng</h3>

    <p>
        <strong>Mã đơn hàng:</strong> {{ $order->order_code }} <br>
        <strong>Người nhận:</strong> {{ $order->name }} <br>
        <strong>Email:</strong> {{ $order->email }} <br>
        <strong>Số điện thoại:</strong> {{ $order->phone }} <br>
        <strong>Địa chỉ:</strong> {{ $order->address }} <br>
        <strong>Phương thức thanh toán:</strong>
        {{ $order->payment_method ? $order->payment_method->name : 'Không xác định' }}
    </p>

    <h3>Chi tiết sản phẩm</h3>

    <table border="1" cellpadding="8" cellspacing="0" width="100%" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th align="left">Sản phẩm</th>
                <th align="center">Số lượng</th>
                <th align="right">Đơn giá</th>
                <th align="right">Thành tiền</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp

            @foreach($order->order_details as $detail)
                @php
                    $lineTotal = $detail->price * $detail->quantity;
                    $total += $lineTotal;
                @endphp

                <tr>
                    <td>
                        @if($detail->product_detail && $detail->product_detail->product)
                            {{ $detail->product_detail->product->name }}

                            @if($detail->product_detail->color)
                                - Màu {{ $detail->product_detail->color }}
                            @endif
                        @else
                            Sản phẩm
                        @endif
                    </td>
                    <td align="center">{{ $detail->quantity }}</td>
                    <td align="right">{{ number_format($detail->price) }} đ</td>
                    <td align="right">{{ number_format($lineTotal) }} đ</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3" align="right"><strong>Tổng tiền:</strong></td>
                <td align="right"><strong>{{ number_format($total) }} đ</strong></td>
            </tr>
        </tfoot>
    </table>

    <p style="margin-top: 20px;">
        Chúng tôi sẽ xử lý đơn hàng và liên hệ với bạn trong thời gian sớm nhất.
    </p>

    <p>
        Trân trọng,<br>
        <strong>Phone Store</strong>
    </p>
</body>
</html>