<?php
namespace App\Http\Controllers;

use App\Income;
use App\Expense;
use Carbon\Carbon;
// use DB;
use Illuminate\Http\Request;

class MonthlyReportsController extends Controller
{
    public function index(Request $r)
    {
      echo $from    = Carbon::parse(sprintf(
            '%s-%s-01',
            $r->query('y', Carbon::now()->year),
            $r->query('m', Carbon::now()->month)
        ));
       $to      = clone $from;
        echo $mytime = Carbon::now()->toDateTimeString();
        // echo $mytime->toDateTimeString();
        $to->day = $to->daysInMonth;

        $exp_q = Expense::with('expenses_category')
            ->whereBetween('entry_date', [$from, $to]);
        // echo"<pre>";print_r($exp_q->get()->toArray());echo"</pre>";exit;
        // \DB::listen(function($exp_q) {
        // echo"<pre>";print_r($exp_q);echo"</pre>";exit;
        // });
        $inc_q = Income::with('income_category')
            ->whereBetween('entry_date', [$from, $to]);
        // \DB::listen(function($inc_q) {
        // echo"<pre>";print_r($inc_q);echo"</pre>";exit;
        // });
        $exp_total = $exp_q->sum('amount');
        $inc_total = $inc_q->sum('amount');
        $exp_group = $exp_q->orderBy('amount', 'desc')->groupBy('expenses_category_id');
        // echo"<pre>";print_r($exp_group->toSql());echo"</pre>";exit;
        

        $inc_group = $inc_q->orderBy('amount', 'desc')->groupBy('income_category_id');
        
        $profit    = $inc_total - $exp_total;

        $exp_summary = [];
        foreach ($exp_group as $exp) {
            foreach ($exp as $line) {
                if (!isset($exp_summary[$line->expenses_category->name])) {
                    $exp_summary[$line->expenses_category->name] = [
                        'name'   => $line->expenses_category->name,
                        'amount' => 0,
                    ];
                }
                $exp_summary[$line->expenses_category->name]['amount'] += $line->amount;
            }
        }

        $inc_summary = [];
        foreach ($inc_group as $inc) {
            foreach ($inc as $line) {
                if (!isset($inc_summary[$line->income_category->name])) {
                    $inc_summary[$line->income_category->name] = [
                        'name'   => $line->income_category->name,
                        'amount' => 0,
                    ];
                }
                $inc_summary[$line->income_category->name]['amount'] += $line->amount;
            }
        }

        return view('monthly_reports.index', compact(
            'exp_summary',
            'inc_summary',
            'exp_total',
            'inc_total',
            'profit'
        ));
    }
}
