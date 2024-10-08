<?php

namespace App\Http\Controllers;

use App\Models\About;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Slider;
use App\Models\Testimoni;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    public function index()
    {
        $sliders = Slider::all();
        $categories = Category::all();
        $testimonies = Testimoni::all();
        $products = Product::skip(0)->take(8)->get();

        return view('home.index', compact('sliders', 'categories', 'testimonies', 'products'));
    }

    public function products($id_subcategory)
    {
        $products = Product::where('id_subkategori', $id_subcategory)->get();

        return view('home.products', compact('products'));
    }

    public function add_to_cart(Request $request)
    {
        $input = $request->all();
        Cart::create($input);
    }

    public function delete_from_cart(Cart $cart)
    {
        $cart->delete();
        return redirect('/cart');
    }

    public function product($id_product)
    {
        $product = Product::find($id_product);
        $latest_products = Product::orderByDesc('created_at')->offset(0)->limit(10)->get();

        return view('home.product', compact('product', 'latest_products'));
    }

    public function cart()
    {
        if (!Auth::guard('webmember')->user()) {
            return redirect('/login_member');
        }

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.rajaongkir.com/starter/province",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "key: e3171a515518ac6f5e817c3322879d97"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        }

        $provinsi = json_decode($response);
        $carts = Cart::where('id_member', Auth::guard('webmember')->user()->id)->where('is_checkout', 0)->get();
        $cart_total = Cart::where('id_member', Auth::guard('webmember')->user()->id)->where('is_checkout', 0)->sum('total');
        // dd($carts);

        return view('home.cart', compact('carts', 'provinsi', 'cart_total'));
    }

    public function get_kota($id)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.rajaongkir.com/starter/city?province=" . $id,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "key: e3171a515518ac6f5e817c3322879d97"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    public function get_ongkir($destination, $weight)
    {
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.rajaongkir.com/starter/cost",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "origin=22&destination=" . $destination . "&weight=" . $weight . "&courier=jne",
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded",
                "key: e3171a515518ac6f5e817c3322879d97"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        } else {
            echo $response;
        }
    }

    public function checkout_orders(Request $request)
    {
        $id = DB::table('orders')->insertGetId([
            'id_member' => $request->id_member,
            'invoice' => date('ymds'),
            'grand_total' => $request->grand_total,
            'status' => 'Baru',
            'created_at' => date('Y-m-d H:i:s')
        ]);


        for ($i = 0; $i < count($request->id_produk); $i++) {
            DB::table('order_details')->insert([
                'id_order' => $id,
                'id_produk' => $request->id_produk[$i],
                'jumlah' => $request->jumlah[$i],
                'total' => $request->total[$i],
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        Cart::where('id_member', Auth::guard('webmember')->user()->id)->update([
            'is_checkout' => 1
        ]);
    }

    public function checkout()
    {
        $about = About::first();
        $orders = Order::where('id_member', Auth::guard('webmember')->user()->id)->orderBy('id', 'desc')->first();
        // dd($orders);


        // dd($snapToken);

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.rajaongkir.com/starter/province",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "key: e3171a515518ac6f5e817c3322879d97"
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            echo "cURL Error #:" . $err;
        }

        $provinsi = json_decode($response);

        return view('home.checkout', compact('about', 'orders', 'provinsi'));
    }

    public function payments(Request $request)
    {
        $about = About::first();

        $orders = Order::where('id_member', Auth::guard('webmember')->user()->id)->orderBy('id', 'desc')->first();

        $request->validate([
            'jumlah'               => "required",
            'no_rekening'               => "required",
            'atas_nama'               => "required",
            'detail_alamat'               => "required",
        ]);

        Payment::create([
            'id_order' => $request->id_order,
            'id_member' => Auth::guard('webmember')->user()->id,
            'jumlah' => $request->jumlah,
            'detail_alamat' => $request->detail_alamat,
            'status' => 'MENUNGGU',
            'no_rekening' => $request->no_rekening,
            'atas_nama' => $request->atas_nama,
        ]);

        // dd($request->all());
        \Midtrans\Config::$serverKey = config('midtrans.server_key');
        \Midtrans\Config::$isProduction = config('midtrans.is_production');
        \Midtrans\Config::$isSanitized = true;
        \Midtrans\Config::$is3ds = true;

        $params = array(
            'transaction_details' => array(
                'order_id' => $request->id_order,
                'gross_amount' => $request->jumlah,
            ),
            'customer_details' => array(
                'first_name' => $request->atas_nama,
                'last_name' => '',
                'phone' => '',
            ),
        );
        $snapToken = \Midtrans\Snap::getSnapToken($params);
        // dd($snapToken);
        return view('home.invoice', compact('snapToken', 'orders', 'about'));
    }

    public function callback(Request $request)
    {
        $serverKey = config('midtrans.server_key');
        $hashed = hash('sha512', $request->order_id . $request->status_code . $request->gross_amount . $serverKey);
        if ($hashed == $request->signature_key) {
            if ($request->transaction_status == 'settlement') {
                $order = Payment::where('id_order', $request->order_id);
                // dd($order);
                $order->update(['status' => 'paid']);
            }
        }
    }


    public function orders()
    {
        $payments = Payment::where('id_member', Auth::guard('webmember')->user()->id)->get();
        return view('home.orders', compact('payments'));
    }

    public function pesanan_selesai(Order $order)
    {
        $order->status = 'Selesai';
        $order->save();

        return redirect('/orders');
    }

    public function about()
    {
        $about = About::first();
        $testimonies = Testimoni::all();

        return view('home.about', compact('about', 'testimonies'));
    }

    public function contact()
    {
        $about = About::first();

        return view('home.contact', compact('about'));
    }

    public function faq()
    {
        return view('home.faq');
    }
}
