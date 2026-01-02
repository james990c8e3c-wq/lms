<style>
    @media only screen and (max-width: 600px) {
        .desktop-table { display: none !important; }
        .mobile-card { display: block !important; }
    }
    @media only screen and (min-width: 601px) {
        .desktop-table { display: table !important; }
        .mobile-card { display: none !important; }
    }
</style>
@if(!empty($bookings))
    <h3 style="font-size: 14px; font-weight: 500; color: #585858; line-height: 20px;">{{ __('courses::courses.sessions_detail') }}</h3>
    <div style="background: #FAF8F5; padding: 16px 11px; border-radius: 8px; margin-top: 16px">
        <!-- Desktop/Tablet Table View -->
        <table class="desktop-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                <th style="font-size: 14px; padding: 12px 10px; font-weight: 500; color: #585858; line-height: 20px; text-align: left; white-space: nowrap;">
                    {{ 
                        $emailFor == 'tutor' 
                        ? (!empty(setting('_lernen.student_display_name')) ? setting('_lernen.student_display_name') : __('general.student')) 
                        : (!empty(setting('_lernen.tutor_display_name')) ? setting('_lernen.tutor_display_name') : __('general.tutor')) 
                    }}
                </th>
                <th style="font-size: 14px; padding: 12px 10px; font-weight: 500; color: #585858; line-height: 20px; text-align: left;">{{ __('booking.subject') }}</th>
                <th style="font-size: 14px; padding: 12px 10px; font-weight: 500; color: #585858; line-height: 20px; text-align: left;">{{ __('calendar.date_time') }}</th>
            </tr>
            </thead>
            <tbody>
                @foreach($bookings as $booking)
                    <tr>
                        @php
                            $img  = $emailFor == 'student' ? $booking['tutorImg'] : $booking['studentImg'];
                            $name = $emailFor == 'student' ? $booking['tutorName'] : $booking['studentName'];
                        @endphp
                        <td style="padding: 10px; font-size: 14px; font-weight: 400; color: #585858; line-height: 20px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 36px; vertical-align: middle; padding-right: 10px;">
                                        @if (!empty($img) && Storage::disk(getStorageDisk())->exists($img))
                                            <img style="width: 36px; height: 36px; border-radius: 50%; display: block;"  src="{{ resizedImage($img, 40, 40) }}" alt="{{ $name }}">
                                        @else 
                                            <img style="width: 36px; height: 36px; border-radius: 50%; display: block;"  src="{{ setting('_general.default_avatar_for_user') ? url(Storage::url(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png', 40, 40) }}" alt="{{ $name }}">
                                        @endif
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <h6 style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px; margin: 0;">{{ $name }}</h6>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="font-size: 14px; padding: 10px; font-weight: 400; color: #585858; line-height: 20px;">{!! $booking['subjectName'] !!} </td>
                        <td style="font-size: 14px; padding: 10px; font-weight: 400; color: #585858; line-height: 20px;">{!! $booking['sessionTime'] !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Mobile Card View -->
        <div class="mobile-card">
            @foreach($bookings as $booking)
                @php
                    $img  = $emailFor == 'student' ? $booking['tutorImg'] : $booking['studentImg'];
                    $name = $emailFor == 'student' ? $booking['tutorName'] : $booking['studentName'];
                @endphp
                <div style="background: #ffffff; padding: 16px; margin-bottom: 12px; border-radius: 8px; border: 1px solid #e0e0e0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding-bottom: 12px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="width: 36px; vertical-align: middle; padding-right: 10px;">
                                            @if (!empty($img) && Storage::disk(getStorageDisk())->exists($img))
                                                <img style="width: 36px; height: 36px; border-radius: 50%; display: block;"  src="{{ resizedImage($img, 40, 40) }}" alt="{{ $name }}">
                                            @else 
                                                <img style="width: 36px; height: 36px; border-radius: 50%; display: block;"  src="{{ setting('_general.default_avatar_for_user') ? url(Storage::url(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png', 40, 40) }}" alt="{{ $name }}">
                                            @endif
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <div style="font-size: 12px; font-weight: 500; color: #888888; line-height: 16px; margin-bottom: 4px;">
                                                {{ 
                                                    $emailFor == 'tutor' 
                                                    ? (!empty(setting('_lernen.student_display_name')) ? setting('_lernen.student_display_name') : __('general.student')) 
                                                    : (!empty(setting('_lernen.tutor_display_name')) ? setting('_lernen.tutor_display_name') : __('general.tutor')) 
                                                }}
                                            </div>
                                            <h6 style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px; margin: 0;">{{ $name }}</h6>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-bottom: 12px; border-top: 1px solid #e8e8e8; padding-top: 12px;">
                                <div style="font-size: 12px; font-weight: 500; color: #888888; line-height: 16px; margin-bottom: 4px;">{{ __('booking.subject') }}</div>
                                <div style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px;">{!! $booking['subjectName'] !!}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-size: 12px; font-weight: 500; color: #888888; line-height: 16px; margin-bottom: 4px;">{{ __('calendar.date_time') }}</div>
                                <div style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px;">{!! $booking['sessionTime'] !!}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
@endif
@if(!empty($courses))
<h3 style="font-size: 14px; font-weight: 500; color: #585858; line-height: 20px;">{{ __('courses::courses.courses_detail') }}</h3>
    <div style="background: #FAF8F5; padding: 16px 11px; border-radius: 8px; margin-top: 16px">
        <!-- Desktop/Tablet Table View -->
        <table class="desktop-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                <th style="font-size: 14px; padding: 12px 10px; font-weight: 500; color: #585858; line-height: 20px; text-align: left; white-space: nowrap;">{{ $emailFor == 'tutor' ? (setting('_lernen.student_display_name') ?? __('general.student')) : (setting('_lernen.tutor_display_name') ?? __('general.tutor')) }}</th>
                <th style="font-size: 14px; padding: 12px 10px; font-weight: 500; color: #585858; line-height: 20px; text-align: left;">{{ __('courses::courses.course_title') }}</th>
                @if(isPaidSystem())
                    <th style="font-size: 14px; padding: 12px 10px; font-weight: 500; color: #585858; line-height: 20px; text-align: left;">{{ __('courses::courses.price') }}</th>
                @endif
                </tr>
            </thead>
            <tbody>
                @foreach($courses as $course)
                    <tr>
                        @php
                            $img  = $emailFor == 'student' ? $course['tutorImg'] : $course['studentImg'];
                            $name = $emailFor == 'student' ? $course['tutorName'] : $course['studentName'];
                        @endphp
                        <td style="padding: 10px; font-size: 14px; font-weight: 400; color: #585858; line-height: 20px;">
                            <table style="width: 100%; border-collapse: collapse;">
                                <tr>
                                    <td style="width: 36px; vertical-align: middle; padding-right: 10px;">
                                        @if (!empty($img) && Storage::disk(getStorageDisk())->exists($img))
                                            <img style="width: 36px; height: 36px; border-radius: 50%; display: block;"  src="{{ resizedImage($img, 40, 40) }}" alt="{{ $name }}">
                                        @else 
                                            <img style="width: 36px; height: 36px; border-radius: 50%; display: block;"  src="{{ setting('_general.default_avatar_for_user') ? url(Storage::url(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png', 40, 40) }}" alt="{{ $name }}">
                                        @endif
                                    </td>
                                    <td style="vertical-align: middle;">
                                        <h6 style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px; margin: 0;">{{ $name }}</h6>
                                    </td>
                                </tr>
                            </table>
                        </td>
                        <td style="font-size: 14px; padding: 10px; font-weight: 400; color: #585858; line-height: 20px;">{!! $course['courseTitle'] !!} </td>
                        @if(isPaidSystem())
                            <td style="font-size: 14px; padding: 10px; font-weight: 400; color: #585858; line-height: 20px;">{!! formatAmount($course['coursePrice']) !!}</td>    
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Mobile Card View -->
        <div class="mobile-card">
            @foreach($courses as $course)
                @php
                    $img  = $emailFor == 'student' ? $course['tutorImg'] : $course['studentImg'];
                    $name = $emailFor == 'student' ? $course['tutorName'] : $course['studentName'];
                @endphp
                <div style="background: #ffffff; padding: 16px; margin-bottom: 12px; border-radius: 8px; border: 1px solid #e0e0e0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding-bottom: 12px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="width: 36px; vertical-align: middle; padding-right: 10px;">
                                            @if (!empty($img) && Storage::disk(getStorageDisk())->exists($img))
                                                <img style="width: 36px; height: 36px; border-radius: 50%; display: block;"  src="{{ resizedImage($img, 40, 40) }}" alt="{{ $name }}">
                                            @else 
                                                <img style="width: 36px; height: 36px; border-radius: 50%; display: block;"  src="{{ setting('_general.default_avatar_for_user') ? url(Storage::url(setting('_general.default_avatar_for_user')[0]['path'])) : resizedImage('placeholder.png', 40, 40) }}" alt="{{ $name }}">
                                            @endif
                                        </td>
                                        <td style="vertical-align: middle;">
                                            <div style="font-size: 12px; font-weight: 500; color: #888888; line-height: 16px; margin-bottom: 4px;">
                                                {{ $emailFor == 'tutor' ? (setting('_lernen.student_display_name') ?? __('general.student')) : (setting('_lernen.tutor_display_name') ?? __('general.tutor')) }}
                                            </div>
                                            <h6 style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px; margin: 0;">{{ $name }}</h6>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-bottom: 12px; border-top: 1px solid #e8e8e8; padding-top: 12px;">
                                <div style="font-size: 12px; font-weight: 500; color: #888888; line-height: 16px; margin-bottom: 4px;">{{ __('courses::courses.course_title') }}</div>
                                <div style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px;">{!! $course['courseTitle'] !!}</div>
                            </td>
                        </tr>
                        @if(isPaidSystem())
                        <tr>
                            <td>
                                <div style="font-size: 12px; font-weight: 500; color: #888888; line-height: 16px; margin-bottom: 4px;">{{ __('courses::courses.price') }}</div>
                                <div style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px;">{!! formatAmount($course['coursePrice']) !!}</div>
                            </td>
                        </tr>
                        @endif
                    </table>
                </div>
            @endforeach
        </div>
    </div>
@endif 
@if(!empty($subscriptions))
<h3 style="font-size: 14px; font-weight: 500; color: #585858; line-height: 20px;">{{ __('subscriptions::subscription.subscriptions_detail') }}</h3>
    <div style="background: #FAF8F5; padding: 16px 11px; border-radius: 8px; margin-top: 16px">
        <!-- Desktop/Tablet Table View -->
        <table class="desktop-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="font-size: 14px; padding: 12px 10px; font-weight: 500; color: #585858; line-height: 20px; text-align: left;">{{ __('subscriptions::subscription.subscription') }}</th>
                    <th style="font-size: 14px; padding: 12px 10px; font-weight: 500; color: #585858; line-height: 20px; text-align: left;">{{ __('subscriptions::subscription.price') }}</th>
                    <th style="font-size: 14px; padding: 12px 10px; font-weight: 500; color: #585858; line-height: 20px; text-align: left;">{{ __('subscriptions::subscription.valid_till') }}</th>
            </tr>
            </thead>
            <tbody>
                @foreach($subscriptions as $subscription)
                    <tr>
                        <td style="font-size: 14px; padding: 10px; font-weight: 400; color: #585858; line-height: 20px;">{!! $subscription['subscriptionName'] . ' (' . __('subscriptions::subscription.'.$subscription['subscriptionPeriod']) . ')' !!} </td>
                        <td style="font-size: 14px; padding: 10px; font-weight: 400; color: #585858; line-height: 20px;">{!! formatAmount($subscription['subscriptionPrice']) !!}</td>
                        <td style="font-size: 14px; padding: 10px; font-weight: 400; color: #585858; line-height: 20px;">{!! Carbon\Carbon::parse($subscription['expires_at'])->format(setting('_general.date_format') ?? 'd M Y') !!}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        
        <!-- Mobile Card View -->
        <div class="mobile-card">
            @foreach($subscriptions as $subscription)
                <div style="background: #ffffff; padding: 16px; margin-bottom: 12px; border-radius: 8px; border: 1px solid #e0e0e0;">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding-bottom: 12px;">
                                <div style="font-size: 12px; font-weight: 500; color: #888888; line-height: 16px; margin-bottom: 4px;">{{ __('subscriptions::subscription.subscription') }}</div>
                                <div style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px;">{!! $subscription['subscriptionName'] . ' (' . __('subscriptions::subscription.'.$subscription['subscriptionPeriod']) . ')' !!}</div>
                            </td>
                        </tr>
                        <tr>
                            <td style="padding-bottom: 12px; border-top: 1px solid #e8e8e8; padding-top: 12px;">
                                <div style="font-size: 12px; font-weight: 500; color: #888888; line-height: 16px; margin-bottom: 4px;">{{ __('subscriptions::subscription.price') }}</div>
                                <div style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px;">{!! formatAmount($subscription['subscriptionPrice']) !!}</div>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <div style="font-size: 12px; font-weight: 500; color: #888888; line-height: 16px; margin-bottom: 4px;">{{ __('subscriptions::subscription.valid_till') }}</div>
                                <div style="font-size: 14px; font-weight: 400; color: #585858; line-height: 20px;">{!! Carbon\Carbon::parse($subscription['expires_at'])->format(setting('_general.date_format') ?? 'd M Y') !!}</div>
                            </td>
                        </tr>
                    </table>
                </div>
            @endforeach
        </div>
    </div>
@endif    