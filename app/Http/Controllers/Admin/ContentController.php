<?php

namespace App\Http\Controllers\Admin;

use App\Events\AdminContentUpdate;
use App\Http\Controllers\Controller;
use App\Models\Admin\Service;
use App\Models\Admin\Testimonial;
use App\Models\Admin\Faq;
use App\Models\Admin\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContentController extends Controller
{
    // Services
    public function services()
    {
        $items = Service::orderBy('id', 'ASC')->get();
        return view('admin.pages.services', compact('items'));
    }

    public function serAdd(Request $request): JsonResponse
    {
        $request->validate([
            'svc_icon'        => 'required|string|max:30',
            'svc_title'       => 'required|string|max:50',
            'svc_description' => 'required|string|max:500',
            'svc_status'      => 'required|integer',
        ], [
            'svc_icon.required' => 'Service icon is required.',
            'svc_icon.string'   => 'Service icon must be valid text.',
            'svc_icon.max'      => 'Service icon cannot exceed 30 characters.',
            'svc_title.required' => 'Service title is required.',
            'svc_title.string'   => 'Service title must be valid text.',
            'svc_title.max'      => 'Service title cannot exceed 50 characters.',
            'svc_description.required' => 'Service description is required.',
            'svc_description.string'   => 'Service description must be valid text.',
            'svc_description.max'      => 'Service description cannot exceed 500 characters.',
            'svc_status.required' => 'Service status is required.',
            'svc_status.integer'  => 'Service status must be a valid number.',
        ]);
            
        DB::beginTransaction();
        try {
            $actor = Auth::guard('admin')->user();

            $item = Service::create([
                'icon'        => $request->svc_icon,
                'title'       => $request->svc_title,
                'description' => $request->svc_description,
                'status'      => $request->svc_status,
                'added_by'    => $actor->username,
            ]);

            DB::commit();
            logHistory($actor->username, $request->ip(), "Added service: {$item->title}");
            event(new AdminContentUpdate(
                entity: 'service',
                action: 'created',
                data: [
                    'id' => $item->id,
                    'icon' => $item->icon,
                    'title' => $item->title,
                    'description' => $item->description,
                    'status' => $item->status,
                    'added_by'    => $item->added_by,
                    'created_at'    => $item->created_at,
                    'updated_by'    => $item->updated_by,
                    'updated_at'    => $item->updated_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Service added.', 'item' => $item]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function serUpdate(Request $request, $id): JsonResponse
    {
        $request->validate([
            'svc_icon'        => 'required|string|max:30',
            'svc_title'       => 'required|string|max:50',
            'svc_description' => 'required|string|max:500',
            'svc_status'      => 'required|integer',
        ], [
            'svc_icon.required' => 'Service icon is required.',
            'svc_icon.string'   => 'Service icon must be valid text.',
            'svc_icon.max'      => 'Service icon cannot exceed 30 characters.',
            'svc_title.required' => 'Service title is required.',
            'svc_title.string'   => 'Service title must be valid text.',
            'svc_title.max'      => 'Service title cannot exceed 50 characters.',
            'svc_description.required' => 'Service description is required.',
            'svc_description.string'   => 'Service description must be valid text.',
            'svc_description.max'      => 'Service description cannot exceed 500 characters.',
            'svc_status.required' => 'Service status is required.',
            'svc_status.integer'  => 'Service status must be a valid number.',
        ]);
            
        DB::beginTransaction();
        try {
            $actor = Auth::guard('admin')->user();
            $item  = Service::findOrFail($id);

            $item->update([
                'icon'        => $request->svc_icon,
                'title'       => $request->svc_title,
                'description' => $request->svc_description,
                'status'      => $request->svc_status,
                'updated_by'  => $actor->username,
            ]);

            DB::commit();
            logHistory($actor->username, $request->ip(), "Updated service: {$item->title}");
            event(new AdminContentUpdate(
                entity: 'service',
                action: 'updated',
                data: [
                    'id' => $item->id,
                    'icon' => $item->icon,
                    'title' => $item->title,
                    'description' => $item->description,
                    'status' => $item->status,
                    'added_by'    => $item->added_by,
                    'created_at'    => $item->created_at,
                    'updated_by'    => $item->updated_by,
                    'updated_at'    => $item->updated_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Service updated.', 'item' => $item]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function serDelete($id): JsonResponse
    {
        $item = Service::findOrFail($id);
        $actor = Auth::guard('admin')->user();
        $itemId = $item->id;
        logHistory($actor->username, request()->ip(), "Deleted service: {$item->title}");
        $item->delete();
        event(new AdminContentUpdate(
            entity: 'service',
            action: 'deleted',
            data: [
                'id' => $itemId,
            ]
        ));
        return response()->json(['success' => true, 'message' => 'Service deleted.']);
    }

    // Testimonials
    public function testimonials()
    {
        $items = Testimonial::orderBy('id', 'ASC')->get();
        return view('admin.pages.testimonials', compact('items'));
    }

    public function testiAdd(Request $request): JsonResponse
    {
        $request->validate([
            'svc_name'    => 'required|string|max:30',
            'svc_role'    => 'nullable|string|max:30',
            'svc_content' => 'required|string|max:500',
            'svc_rating'  => 'required|integer|min:1|max:5',
            'svc_status'  => 'required|integer',
        ], [
            'svc_name.required' => 'Name is required.',
            'svc_name.string'   => 'Name must be valid text.',
            'svc_name.max'      => 'Name cannot exceed 30 characters.',
            'svc_role.string'   => 'Role must be valid text.',
            'svc_role.max'      => 'Role cannot exceed 30 characters.',
            'svc_content.required' => 'Content is required.',
            'svc_content.string'   => 'Content must be valid text.',
            'svc_content.max'      => 'Content cannot exceed 500 characters.',
            'svc_rating.required' => 'Rating is required.',
            'svc_rating.integer'  => 'Rating must be a whole number.',
            'svc_rating.min'      => 'Rating must be at least 1.',
            'svc_rating.max'      => 'Rating cannot be greater than 5.',
            'svc_status.required' => 'Status is required.',
            'svc_status.integer'  => 'Status must be a valid number.',
        ]);
            
        DB::beginTransaction();
        try {
            $actor = Auth::guard('admin')->user();

            $item = Testimonial::create([
                'name'     => $request->svc_name,
                'role'     => $request->svc_role,
                'content'  => $request->svc_content,
                'rating'   => $request->svc_rating,
                'status'   => $request->svc_status,
                'added_by' => $actor->username,
            ]);

            DB::commit();
            logHistory($actor->username, $request->ip(), "Added testimonial from: {$item->name}");
            event(new AdminContentUpdate(
                entity: 'testimonial',
                action: 'created',
                data: [
                    'id' => $item->id,
                    'name' => $item->name,
                    'role' => $item->role,
                    'content' => $item->content,
                    'rating' => $item->rating,
                    'status' => $item->status,
                    'added_by'    => $item->added_by,
                    'created_at'    => $item->created_at,
                    'updated_by'    => $item->updated_by,
                    'updated_at'    => $item->updated_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Testimonial added.', 'item' => $item]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function testiUpdate(Request $request, $id): JsonResponse
    {
        $request->validate([
            'svc_name'    => 'required|string|max:30',
            'svc_role'    => 'nullable|string|max:30',
            'svc_content' => 'required|string|max:500',
            'svc_rating'  => 'required|integer|min:1|max:5',
            'svc_status'  => 'required|integer',
        ], [
            'svc_name.required' => 'Name is required.',
            'svc_name.string'   => 'Name must be valid text.',
            'svc_name.max'      => 'Name cannot exceed 30 characters.',
            'svc_role.string'   => 'Role must be valid text.',
            'svc_role.max'      => 'Role cannot exceed 30 characters.',
            'svc_content.required' => 'Content is required.',
            'svc_content.string'   => 'Content must be valid text.',
            'svc_content.max'      => 'Content cannot exceed 500 characters.',
            'svc_rating.required' => 'Rating is required.',
            'svc_rating.integer'  => 'Rating must be a whole number.',
            'svc_rating.min'      => 'Rating must be at least 1.',
            'svc_rating.max'      => 'Rating cannot be greater than 5.',
            'svc_status.required' => 'Status is required.',
            'svc_status.integer'  => 'Status must be a valid number.',
        ]);

        DB::beginTransaction();
        try {
            $actor = Auth::guard('admin')->user();
            $item  = Testimonial::findOrFail($id);

            $item->update([
                'name'       => $request->svc_name,
                'role'       => $request->svc_role,
                'content'    => $request->svc_content,
                'rating'     => $request->svc_rating,
                'status'     => $request->svc_status,
                'updated_by' => $actor->username,
            ]);

            DB::commit();
            logHistory($actor->username, $request->ip(), "Updated testimonial from: {$item->name}");
            event(new AdminContentUpdate(
                entity: 'testimonial',
                action: 'updated',
                data: [
                    'id' => $item->id,
                    'name' => $item->name,
                    'role' => $item->role,
                    'content' => $item->content,
                    'rating' => $item->rating,
                    'status' => $item->status,
                    'added_by'    => $item->added_by,
                    'created_at'    => $item->created_at,
                    'updated_by'    => $item->updated_by,
                    'updated_at'    => $item->updated_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Testimonial updated.', 'item' => $item]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function testiDelete($id): JsonResponse
    {
        $item = Testimonial::findOrFail($id);
        $actor = Auth::guard('admin')->user();
        $itemId = $item->id;
        logHistory($actor->username, request()->ip(), "Deleted testimonial: {$item->name}");
        $item->delete();
        event(new AdminContentUpdate(
            entity: 'testimonial',
            action: 'deleted',
            data: [
                'id' => $itemId,
            ]
        ));
        return response()->json(['success' => true, 'message' => 'Testimonial deleted.']);
    }

    // FAQs
    public function faqs()
    {
        $items = Faq::orderBy('id', 'ASC')->get();
        return view('admin.pages.faqs', compact('items'));
    }

    public function faqsAdd(Request $request): JsonResponse
    {
        $request->validate([
            'svc_question' => 'required|string|max:400',
            'svc_answer'   => 'required|string|max:2000',
            'svc_status'   => 'required|integer',
        ], [
            'svc_question.required' => 'Question is required.',
            'svc_question.string'   => 'Question must be valid text.',
            'svc_question.max'      => 'Question cannot exceed 400 characters.',
            'svc_answer.required' => 'Answer is required.',
            'svc_answer.string'   => 'Answer must be valid text.',
            'svc_answer.max'      => 'Answer cannot exceed 2000 characters.',
            'svc_status.required' => 'Status is required.',
            'svc_status.integer'  => 'Status must be a valid number.',
        ]);
            
        DB::beginTransaction();
        try {
            $actor = Auth::guard('admin')->user();

            $item = Faq::create([
                'question' => $request->svc_question,
                'answer'   => $request->svc_answer,
                'status'   => $request->svc_status,
                'added_by' => $actor->username,
            ]);

            DB::commit();
            logHistory($actor->username, $request->ip(), "Added FAQ: " . substr($item->question, 0, 60));
            event(new AdminContentUpdate(
                entity: 'faq',
                action: 'created',
                data: [
                    'id' => $item->id,
                    'question' => $item->question,
                    'answer' => $item->answer,
                    'status' => $item->status,
                    'added_by'    => $item->added_by,
                    'created_at'    => $item->created_at,
                    'updated_by'    => $item->updated_by,
                    'updated_at'    => $item->updated_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'FAQ added.', 'item' => $item]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function faqsUpdate(Request $request, $id): JsonResponse
    {
        $request->validate([
            'svc_question' => 'required|string|max:400',
            'svc_answer'   => 'required|string|max:2000',
            'svc_status'   => 'required|integer',
        ], [
            'svc_question.required' => 'Question is required.',
            'svc_question.string'   => 'Question must be valid text.',
            'svc_question.max'      => 'Question cannot exceed 400 characters.',
            'svc_answer.required' => 'Answer is required.',
            'svc_answer.string'   => 'Answer must be valid text.',
            'svc_answer.max'      => 'Answer cannot exceed 2000 characters.',
            'svc_status.required' => 'Status is required.',
            'svc_status.integer'  => 'Status must be a valid number.',
        ]);

        DB::beginTransaction();
        try {
            $actor = Auth::guard('admin')->user();
            $item  = Faq::findOrFail($id);

            $item->update([
                'question'   => $request->svc_question,
                'answer'     => $request->svc_answer,
                'status'     => $request->svc_status,
                'updated_by' => $actor->username,
            ]);

            DB::commit();
            logHistory($actor->username, $request->ip(), "Updated FAQ: " . substr($item->question, 0, 60));
            event(new AdminContentUpdate(
                entity: 'faq',
                action: 'updated',
                data: [
                    'id' => $item->id,
                    'question' => $item->question,
                    'answer' => $item->answer,
                    'status' => $item->status,
                    'added_by'    => $item->added_by,
                    'created_at'    => $item->created_at,
                    'updated_by'    => $item->updated_by,
                    'updated_at'    => $item->updated_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'FAQ updated.', 'item' => $item]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function faqsDelete($id): JsonResponse
    {
        $item = Faq::findOrFail($id);
        $actor = Auth::guard('admin')->user();
        $itemId = $item->id;
        logHistory($actor->username, request()->ip(), 'Deleted FAQ: ' . substr($item->question, 0, 60));
        $item->delete();
        event(new AdminContentUpdate(
            entity: 'faq',
            action: 'deleted',
            data: [
                'id' => $itemId,
            ]
        ));
        return response()->json(['success' => true, 'message' => 'FAQ deleted.']);
    }

    // Branches
    public function branches()
    {
        $items = Branch::orderBy('id', 'ASC')->get();
        return view('admin.pages.branches', compact('items'));
    }

    public function branchAdd(Request $request): JsonResponse
    {
        $request->validate([
            'svc_branch_name'    => 'required|string|max:30',
            'svc_branch_address' => 'required|string|max:100',
            'svc_branch_phone'   => 'required|string|max:13',
            'svc_branch_email'   => 'required|email|max:100',
            'svc_bstatus'         => 'required|string',
        ], [
            'svc_branch_name.required' => 'Branch name is required.',
            'svc_branch_name.string'   => 'Branch name must be valid text.',
            'svc_branch_name.max'      => 'Branch name cannot exceed 30 characters.',
            'svc_branch_address.required' => 'Branch address is required.',
            'svc_branch_address.string'   => 'Branch address must be valid text.',
            'svc_branch_address.max'      => 'Branch address cannot exceed 100 characters.',
            'svc_branch_phone.required' => 'Branch phone number is required.',
            'svc_branch_phone.string'   => 'Branch phone number must be valid text.',
            'svc_branch_phone.max'      => 'Branch phone number cannot exceed 13 characters.',
            'svc_branch_email.required' => 'Branch email address is required.',
            'svc_branch_email.email'    => 'Please enter a valid email address.',
            'svc_branch_email.max'      => 'Branch email cannot exceed 100 characters.',
            'svc_bstatus.required' => 'Status is required.',
            'svc_bstatus.string'   => 'Status must be valid text.',
        ]);
            
        DB::beginTransaction();
        try {
            $actor = Auth::guard('admin')->user();
            $branch = Branch::create([
                'name'     => $request->svc_branch_name,
                'address'  => $request->svc_branch_address,
                'phone'    => $request->svc_branch_phone,
                'email'    => $request->svc_branch_email,
                'status'   => $request->svc_bstatus,
                'added_by' => $actor->username,
            ]);
            DB::commit();
            logHistory($actor->username, request()->ip(), "Added branch: {$branch->name}");
            event(new AdminContentUpdate(
                entity: 'branches',
                action: 'created',
                data: [
                    'id'     => $branch->id,
                    'name'     => $branch->name,
                    'address'  => $branch->address,
                    'phone'    => $branch->phone,
                    'email'    => $branch->email,
                    'status'   => $branch->status,
                    'added_by'    => $branch->added_by,
                    'created_at'    => $branch->created_at,
                    'updated_by'    => $branch->updated_by,
                    'updated_at'    => $branch->updated_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Branch added.', 'item' => $branch]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function branchUpdate(Request $request, $id): JsonResponse
    {
        $request->validate([
            'svc_branch_name'    => 'required|string|max:30',
            'svc_branch_address' => 'required|string|max:100',
            'svc_branch_phone'   => 'required|string|max:13',
            'svc_branch_email'   => 'required|email|max:100',
            'svc_bstatus'         => 'required|string',
        ], [
            'svc_branch_name.required' => 'Branch name is required.',
            'svc_branch_name.string'   => 'Branch name must be valid text.',
            'svc_branch_name.max'      => 'Branch name cannot exceed 30 characters.',
            'svc_branch_address.required' => 'Branch address is required.',
            'svc_branch_address.string'   => 'Branch address must be valid text.',
            'svc_branch_address.max'      => 'Branch address cannot exceed 100 characters.',
            'svc_branch_phone.required' => 'Branch phone number is required.',
            'svc_branch_phone.string'   => 'Branch phone number must be valid text.',
            'svc_branch_phone.max'      => 'Branch phone number cannot exceed 13 characters.',
            'svc_branch_email.required' => 'Branch email address is required.',
            'svc_branch_email.email'    => 'Please enter a valid email address.',
            'svc_branch_email.max'      => 'Branch email cannot exceed 100 characters.',
            'svc_bstatus.required' => 'Status is required.',
            'svc_bstatus.string'   => 'Status must be valid text.',
        ]);
            
        DB::beginTransaction();
        try {
            $actor = Auth::guard('admin')->user();
            $branch = Branch::findOrFail($id);
            $branch->update([
                'name'       => $request->svc_branch_name,
                'address'    => $request->svc_branch_address,
                'phone'      => $request->svc_branch_phone,
                'email'      => $request->svc_branch_email,
                'status'     => $request->svc_bstatus,
                'updated_by' => $actor->username,
            ]);

            DB::commit();
            logHistory($actor->username, request()->ip(), "Updated branch: {$branch->name}");
            event(new AdminContentUpdate(
                entity: 'branches',
                action: 'updated',
                data: [
                    'id'     => $branch->id,
                    'name'     => $branch->name,
                    'address'  => $branch->address,
                    'phone'    => $branch->phone,
                    'email'    => $branch->email,
                    'status'   => $branch->status,
                    'added_by'    => $branch->added_by,
                    'created_at'    => $branch->created_at,
                    'updated_by'    => $branch->updated_by,
                    'updated_at'    => $branch->updated_at,
                ]
            ));
            return response()->json(['success' => true, 'message' => 'Branch updated.', 'item' => $branch]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function branchDelete($id): JsonResponse
    {
        $item = Branch::findOrFail($id);
        $actor = Auth::guard('admin')->user();
        $itemId = $item->id;
        $name   = $item->name;
        $item->delete();
        logHistory($actor->username, request()->ip(), "Deleted branch: {$name}");
        event(new AdminContentUpdate(
            entity: 'branches',
            action: 'deleted',
            data: [
                'id' => $itemId,
            ]
        ));
        return response()->json(['success' => true, 'message' => 'Branch deleted.']);
    }
}
