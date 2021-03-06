<?php
/**
 * User: Andrey Shamis
 * Date: 09.05.20
 * Time: 12:43
 */

namespace App\Form;

use App\Entity\CycleSearch;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CycleSearchType extends AbstractType
{

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class,
                array(
                    'required' => false,
                    'attr' =>
                        array(
                            'style' => 'width:400px;'
                        )
                ))
            ->add('fromDate', TextType::class, array(
                'required' => false,
                'attr' => array('class' => 'form-control')))
            ->add('toDate', TextType::class, array(
                'required' => false,
                'attr' => array('class' => 'form-control')))
            //->add('timeRun')
//            ->add('verdict', LogBookSelectableVerdictType::class, array('required' => false))
            ->add('setup', LogBookSelectableSetupType::class, array('required' => false))
            ->add('limit', QueryLimitType::class)
            //->add('executionOrder')
//            ->add('cycle')
            //->add('disabled')
        ;

        $nulTransformer = new CallbackTransformer(
            function ($input)
            {
                return null;
            },
            function ($input)
            {
                return null;
            }
        );

//        $builder->get('verdict')->addModelTransformer($nulTransformer);
        $builder->get('setup')->addModelTransformer($nulTransformer);

    }

    /**
     * @param OptionsResolver $resolver
     * @throws \Symfony\Component\OptionsResolver\Exception\AccessException
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // uncomment if you want to bind to a class
//            'compound' => true,
            'data_class' => CycleSearch::class,
        ]);
    }
}